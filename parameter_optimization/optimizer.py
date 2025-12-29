#!/usr/bin/python3
"""
Portfolio Parameter Optimizer

This script automates testing different parameter combinations for opt3.py
to find the optimal configuration that maximizes expected return while
maintaining a Sharpe ratio > 0.9.

Goal: Maximize expected_return where sharpe_ratio > 0.9

Note: Runs opt3.py with -x flag (no database writes, no downloads)
      Stores results in local JSON file after each iteration (incremental save).
"""

import argparse
import fcntl
import json
import random
import re
import subprocess
import sys
import time
from datetime import datetime
from pathlib import Path
import numpy as np
try:
    from sklearn.gaussian_process import GaussianProcessRegressor
    from sklearn.gaussian_process.kernels import RBF, ConstantKernel
    from scipy.stats import norm
    BAYESIAN_AVAILABLE = True
except ImportError:
    BAYESIAN_AVAILABLE = False

# Parse command-line arguments
parser = argparse.ArgumentParser(
    description='Portfolio Parameter Optimizer - Random search for optimal opt3.py parameters',
    epilog='Example: %(prog)s -n 100 -c narrow_ranges.json'
)
parser.add_argument('-n', '--iterations', type=int, default=10, metavar='N',
                    help='number of random parameter combinations to test (default: 10)')
parser.add_argument('-c', '--config', type=str, default='param_ranges.json', metavar='FILE',
                    help='path to parameter ranges config file (default: param_ranges.json)')
parser.add_argument('-r', '--report', action='store_true',
                    help='display tabular report of all accumulated results and exit')
parser.add_argument('-p', '--proximity', action='store_true',
                    help='display top 3 results that improve current return with smallest parameter distance and exit')
parser.add_argument('--avg-runs', type=int, default=10, metavar='N',
                    help='number of recent production runs to average for baseline return (default: 10, used with -p)')
parser.add_argument('-b', '--bayesian', action='store_true',
                    help='use Bayesian optimization guided by existing results')
parser.add_argument('-i', '--interpolate', action='store_true',
                    help='interpolation mode: test midpoints between adjacent results sorted by score')
parser.add_argument('--bins', type=int, default=8, metavar='N',
                    help='number of bins per dimension for coverage calculation (default: 8)')
args = parser.parse_args()

# Configuration
OPT3_SCRIPT = "opt3.py"
MIN_SHARPE_RATIO = 0.9
NUM_ITERATIONS = args.iterations
RESULTS_FILE = "optimization_results.json"
CONFIG_FILE = args.config

# Load parameter ranges from config file
config_path = Path(__file__).parent / CONFIG_FILE
if not config_path.exists():
    print(f"Error: Config file not found: {config_path}")
    print("Creating default config file...")
    default_config = {
        'gamma': {'min': 0.0, 'max': 10.0},
        'rgoal': {'min': 0.05, 'max': 0.20},
        'lb': {'min': 0.0, 'max': 0.05},
        'ub': {'min': 0.10, 'max': 0.30}
    }
    with open(config_path, 'w') as f:
        json.dump(default_config, f, indent=2)
    print(f"Created {config_path}")
    PARAM_RANGES = default_config
else:
    with open(config_path, 'r') as f:
        PARAM_RANGES = json.load(f)


def parse_currentmodel():
    """
    Parse the currentmodel file to extract production parameters.

    Returns:
        dict: Production parameters or None if file not found/parseable
    """
    currentmodel_path = Path(__file__).parent.parent / "currentmodel"

    if not currentmodel_path.exists():
        return None

    try:
        with open(currentmodel_path, 'r') as f:
            for line in f:
                # Look for line like: ./opt3.py 2.111 .1291 0.00168 .0453
                if './opt3.py' in line and not line.strip().startswith('#'):
                    parts = line.split()
                    if len(parts) >= 5:
                        return {
                            'gamma': float(parts[1]),
                            'rgoal': float(parts[2]),
                            'lb': float(parts[3]),
                            'ub': float(parts[4])
                        }
        return None
    except Exception as e:
        print(f"Warning: Could not parse currentmodel file: {e}")
        return None


def parse_production_return(num_runs=10):
    """
    Parse modelrun.log to get the average expected return from the last N production runs.

    Args:
        num_runs: Number of recent runs to average (default: 10)

    Returns:
        float: Average expected return from last N runs or None if file not found/parseable
    """
    modelrun_path = Path(__file__).parent.parent / "modelrun.log"

    if not modelrun_path.exists():
        return None

    try:
        returns = []
        with open(modelrun_path, 'r') as f:
            for line in f:
                # Skip header and comments
                if line.startswith('today,') or line.startswith('#'):
                    continue

                # Parse lines like: 2024-07-22 18:23:15.145092,upper 0.049,lower 0.0015,gamma 1.1,maxvolat 0.121,0.138271,0.12099999996881497,0.977449
                parts = line.strip().split(',')
                if len(parts) >= 6:
                    # The 6th element (index 5) should be expected_return
                    try:
                        returns.append(float(parts[5]))
                    except (ValueError, IndexError):
                        continue

        if not returns:
            return None

        # Take the last N runs and average them
        recent_returns = returns[-num_runs:]
        avg_return = sum(recent_returns) / len(recent_returns)

        return avg_return
    except Exception as e:
        print(f"Warning: Could not parse modelrun.log: {e}")
        return None


def calculate_distance_from_production(params, production_params):
    """
    Calculate normalized Euclidean distance from production parameters.

    Parameters are normalized to [0, 1] range before calculating distance,
    so all parameters contribute equally regardless of their scales.

    Args:
        params: Dict with keys gamma, rgoal, lb, ub
        production_params: Dict with production parameter values

    Returns:
        float: Normalized Euclidean distance (0 = identical to production)
    """
    if production_params is None:
        return None

    # Normalize both parameter sets
    norm_test = normalize_params(
        params['gamma'], params['rgoal'], params['lb'], params['ub']
    )
    norm_prod = normalize_params(
        production_params['gamma'], production_params['rgoal'],
        production_params['lb'], production_params['ub']
    )

    # Calculate Euclidean distance in normalized space
    distance = np.linalg.norm(norm_test - norm_prod)

    return distance


def parse_opt3_output(output):
    """
    Parse opt3.py output to extract performance metrics.

    Args:
        output: String output from opt3.py

    Returns:
        dict: Metrics or None if parsing failed
    """
    try:
        # Look for lines like:
        # Expected annual return: 12.3%
        # Annual volatility: 15.4%
        # Sharpe Ratio: 0.80
        # Weights standard deviation: 0.0234

        expected_return = None
        volatility = None
        sharpe_ratio = None
        weights_stdev = None

        for line in output.split('\n'):
            if 'Expected annual return (8-digit)' in line:
                # New format with 8-digit precision: "Expected annual return (8-digit): 0.12345678 (12.345678%)"
                match = re.search(r':\s*([-+]?\d+\.\d+)', line)
                if match:
                    expected_return = float(match.group(1))
            elif 'Expected annual return' in line and expected_return is None:
                # Fallback to old format: "Expected annual return: 12.3%"
                match = re.search(r'([-+]?\d+\.?\d*)%', line)
                if match:
                    expected_return = float(match.group(1)) / 100.0
            elif 'Annual volatility' in line:
                match = re.search(r'([-+]?\d+\.?\d*)%', line)
                if match:
                    volatility = float(match.group(1)) / 100.0
            elif 'Sharpe Ratio' in line or 'Sharpe ratio' in line:
                match = re.search(r'([-+]?\d+\.?\d+)', line)
                if match:
                    sharpe_ratio = float(match.group(1))
            elif 'Weights standard deviation' in line:
                match = re.search(r'([-+]?\d+\.?\d+)', line)
                if match:
                    weights_stdev = float(match.group(1))

        if all(v is not None for v in [expected_return, volatility, sharpe_ratio, weights_stdev]):
            return {
                'expected_return': expected_return,
                'volatility': volatility,
                'sharpe_ratio': sharpe_ratio,
                'weights_stdev': weights_stdev
            }
        else:
            return None
    except Exception as e:
        print(f"Error parsing output: {e}")
        return None


def run_optimization(gamma, rgoal, lb, ub, production_params=None):
    """
    Run opt3.py with specified parameters using -x flag (no I/O).

    Args:
        gamma: Regularization parameter
        rgoal: Target volatility/risk goal
        lb: Lower bound for individual asset weights
        ub: Upper bound for individual asset weights
        production_params: Production model parameters for distance calculation

    Returns:
        dict: Result containing metrics or None if failed
    """
    try:
        # Call opt3.py directly using its shebang (same as ./opt3.py)
        cmd = [
            './' + OPT3_SCRIPT,
            '-x',  # No I/O mode
            str(gamma),
            str(rgoal),
            str(lb),
            str(ub)
        ]

        result = subprocess.run(
            cmd,
            capture_output=True,
            text=True,
            timeout=120,  # 2 minute timeout
            cwd=Path(__file__).parent.parent
        )

        if result.returncode != 0:
            # Check if it's an infeasible optimization error
            if result.stderr and 'Solver status: infeasible' in result.stderr:
                print(f"  ✗ Infeasible (constraints cannot be satisfied with these parameters)")
            else:
                # Show other errors in detail
                print(f"  Error: opt3.py returned code {result.returncode}")
                if result.stderr:
                    # Show last 500 chars for non-infeasible errors
                    print(f"  stderr: {result.stderr[-500:]}")
            return None

        metrics = parse_opt3_output(result.stdout)
        if metrics:
            params_dict = {
                'gamma': gamma,
                'rgoal': rgoal,
                'lb': lb,
                'ub': ub
            }
            metrics['params'] = params_dict
            metrics['timestamp'] = datetime.now().isoformat()

            # Calculate distance from production parameters
            if production_params:
                metrics['distance_from_production'] = calculate_distance_from_production(
                    params_dict, production_params
                )
            else:
                metrics['distance_from_production'] = None

        return metrics

    except subprocess.TimeoutExpired:
        print(f"  Timeout running optimization")
        return None
    except Exception as e:
        print(f"  Error running optimization: {e}")
        return None


def generate_random_params():
    """
    Generate random parameters within defined ranges.

    Returns:
        tuple: (gamma, rgoal, lb, ub)
    """
    gamma = random.uniform(PARAM_RANGES['gamma']['min'], PARAM_RANGES['gamma']['max'])
    rgoal = random.uniform(PARAM_RANGES['rgoal']['min'], PARAM_RANGES['rgoal']['max'])
    lb = random.uniform(PARAM_RANGES['lb']['min'], PARAM_RANGES['lb']['max'])
    ub = random.uniform(PARAM_RANGES['ub']['min'], PARAM_RANGES['ub']['max'])

    # Ensure lb < ub
    if lb >= ub:
        lb, ub = 0.0, max(0.10, ub)

    return gamma, rgoal, lb, ub


def load_existing_results():
    """
    Load existing results from JSON file with shared locking.
    Calculates scores and distances for all results if not already present.

    Uses fcntl.flock() with LOCK_SH to safely read while allowing concurrent
    reads but blocking if a write is in progress.

    Returns:
        list: Existing results or empty list if file doesn't exist
    """
    filepath = Path(__file__).parent / RESULTS_FILE

    if not filepath.exists():
        return []

    try:
        with open(filepath, 'r') as f:
            # Acquire shared lock (allows multiple readers, blocks writers)
            fcntl.flock(f.fileno(), fcntl.LOCK_SH)

            try:
                results = json.load(f)
            finally:
                # Release shared lock
                fcntl.flock(f.fileno(), fcntl.LOCK_UN)

        # Load production params for distance calculation
        production_params = parse_currentmodel()

        # Calculate scores and distances for results that don't have them
        for r in results:
            if 'score' not in r:
                r['score'] = calculate_score(r)

            # Calculate distance if not present and production params available
            if 'distance_from_production' not in r and production_params:
                r['distance_from_production'] = calculate_distance_from_production(
                    r['params'], production_params
                )

        return results
    except (json.JSONDecodeError, FileNotFoundError):
        return []


def normalize_params(gamma, rgoal, lb, ub):
    """
    Normalize parameters to [0, 1] range for Gaussian Process.

    Returns:
        np.array: Normalized parameters
    """
    norm_gamma = (gamma - PARAM_RANGES['gamma']['min']) / (PARAM_RANGES['gamma']['max'] - PARAM_RANGES['gamma']['min'])
    norm_rgoal = (rgoal - PARAM_RANGES['rgoal']['min']) / (PARAM_RANGES['rgoal']['max'] - PARAM_RANGES['rgoal']['min'])
    norm_lb = (lb - PARAM_RANGES['lb']['min']) / (PARAM_RANGES['lb']['max'] - PARAM_RANGES['lb']['min'])
    norm_ub = (ub - PARAM_RANGES['ub']['min']) / (PARAM_RANGES['ub']['max'] - PARAM_RANGES['ub']['min'])
    return np.array([norm_gamma, norm_rgoal, norm_lb, norm_ub])


def denormalize_params(norm_params):
    """
    Denormalize parameters from [0, 1] range back to original ranges.

    Returns:
        tuple: (gamma, rgoal, lb, ub)
    """
    gamma = norm_params[0] * (PARAM_RANGES['gamma']['max'] - PARAM_RANGES['gamma']['min']) + PARAM_RANGES['gamma']['min']
    rgoal = norm_params[1] * (PARAM_RANGES['rgoal']['max'] - PARAM_RANGES['rgoal']['min']) + PARAM_RANGES['rgoal']['min']
    lb = norm_params[2] * (PARAM_RANGES['lb']['max'] - PARAM_RANGES['lb']['min']) + PARAM_RANGES['lb']['min']
    ub = norm_params[3] * (PARAM_RANGES['ub']['max'] - PARAM_RANGES['ub']['min']) + PARAM_RANGES['ub']['min']

    # Ensure lb < ub
    if lb >= ub:
        lb, ub = 0.0, max(0.10, ub)

    return gamma, rgoal, lb, ub


def expected_improvement(X, gp, y_max, xi=0.01):
    """
    Calculate expected improvement acquisition function.

    Args:
        X: Points to evaluate (normalized)
        gp: Fitted Gaussian Process model
        y_max: Current best observed value
        xi: Exploration parameter

    Returns:
        np.array: Expected improvement values
    """
    mu, sigma = gp.predict(X, return_std=True)

    # Ensure mu and sigma are 1D arrays
    mu = mu.flatten()
    sigma = sigma.flatten()

    with np.errstate(divide='warn'):
        imp = mu - y_max - xi
        Z = np.zeros_like(imp)

        # Only calculate Z where sigma > 0
        mask = sigma > 0
        Z[mask] = imp[mask] / sigma[mask]

        ei = np.zeros_like(imp)
        ei[mask] = imp[mask] * norm.cdf(Z[mask]) + sigma[mask] * norm.pdf(Z[mask])

    return ei


def generate_bayesian_params(existing_results, n_candidates=2500):
    """
    Generate parameters using Bayesian optimization based on existing results.

    Args:
        existing_results: List of previous optimization results
        n_candidates: Number of random candidates to evaluate with acquisition function

    Returns:
        tuple: (gamma, rgoal, lb, ub) suggested by Bayesian optimization
    """
    if not BAYESIAN_AVAILABLE:
        print("  Warning: scikit-learn/scipy not available, falling back to random search")
        return generate_random_params()

    if len(existing_results) < 5:
        print(f"  Bayesian: Only {len(existing_results)} results available, using random (need ≥5)")
        return generate_random_params()

    # Prepare training data from existing results
    X_train = []
    y_train = []

    for result in existing_results:
        params = result['params']
        score = calculate_score(result)

        # Normalize parameters
        norm_params = normalize_params(
            params['gamma'],
            params['rgoal'],
            params['lb'],
            params['ub']
        )

        X_train.append(norm_params)
        y_train.append(score)

    X_train = np.array(X_train)
    y_train = np.array(y_train).reshape(-1, 1)

    # Fit Gaussian Process with expanded bounds to avoid convergence warnings
    # length_scale bounds expanded from default (1e-5, 1e5) to (1e-3, 1e3)
    # This prevents the optimizer from hitting lower bounds
    kernel = ConstantKernel(1.0, constant_value_bounds=(1e-3, 1e3)) * RBF(
        length_scale=0.5,
        length_scale_bounds=(1e-3, 1e3)
    )
    gp = GaussianProcessRegressor(
        kernel=kernel,
        n_restarts_optimizer=2,
        alpha=1e-6,  # Add small noise for numerical stability
        normalize_y=True,  # Normalize target values for better conditioning
        random_state=42
    )

    try:
        gp.fit(X_train, y_train)
    except Exception as e:
        print(f"  Warning: GP fitting failed ({e}), falling back to random search")
        return generate_random_params()

    # Generate random candidates and evaluate with acquisition function
    candidates = np.random.uniform(0, 1, size=(n_candidates, 4))

    # Calculate expected improvement for each candidate
    y_max = np.max(y_train)
    ei_values = expected_improvement(candidates, gp, y_max)

    # Select candidate with highest expected improvement
    best_idx = np.argmax(ei_values)
    best_candidate = candidates[best_idx]

    # Denormalize back to parameter ranges
    gamma, rgoal, lb, ub = denormalize_params(best_candidate)

    print(f"  Bayesian: Using GP trained on {len(existing_results)} results (EI={ei_values[best_idx]:.4f})")

    return gamma, rgoal, lb, ub


def generate_interpolated_params_list(existing_results):
    """
    Generate a list of interpolated parameters from existing results.
    Sorts adjacent pairs by pairwise gap size (descending) and generates midpoints.

    Args:
        existing_results: List of previous optimization results

    Returns:
        list: List of (gamma, rgoal, lb, ub) tuples for interpolated parameters
    """
    if len(existing_results) < 2:
        print(f"  Interpolation: Only {len(existing_results)} results available, need ≥2")
        return []

    # Calculate scores for all results if not present
    for r in existing_results:
        if 'score' not in r:
            r['score'] = calculate_score(r)

    # Generate all pairwise combinations and calculate gaps
    pairs_with_gaps = []
    for i in range(len(existing_results)):
        for j in range(i + 1, len(existing_results)):
            r1 = existing_results[i]
            r2 = existing_results[j]

            # Calculate normalized Euclidean distance between parameter sets
            norm_r1 = normalize_params(
                r1['params']['gamma'], r1['params']['rgoal'],
                r1['params']['lb'], r1['params']['ub']
            )
            norm_r2 = normalize_params(
                r2['params']['gamma'], r2['params']['rgoal'],
                r2['params']['lb'], r2['params']['ub']
            )
            gap = np.linalg.norm(norm_r1 - norm_r2)

            pairs_with_gaps.append((r1, r2, gap))

    # Sort by gap size descending (largest gaps first)
    pairs_with_gaps.sort(key=lambda x: x[2], reverse=True)

    print(f"  Interpolation: Loaded {len(existing_results)} results")
    print(f"  Generated {len(pairs_with_gaps)} pairwise gaps")
    print(f"  Largest gap:  {pairs_with_gaps[0][2]:.6f}")
    print(f"  Smallest gap: {pairs_with_gaps[-1][2]:.6f}")
    print(f"  Generating {len(pairs_with_gaps)} midpoint parameter sets sorted by gap size...")
    print()

    # Generate midpoints for pairs ordered by gap size
    interpolated_params = []
    for r1, r2, gap in pairs_with_gaps:
        # Calculate midpoint for each parameter
        gamma = (r1['params']['gamma'] + r2['params']['gamma']) / 2.0
        rgoal = (r1['params']['rgoal'] + r2['params']['rgoal']) / 2.0
        lb = (r1['params']['lb'] + r2['params']['lb']) / 2.0
        ub = (r1['params']['ub'] + r2['params']['ub']) / 2.0

        # Ensure lb < ub
        if lb >= ub:
            lb, ub = 0.0, max(0.10, ub)

        interpolated_params.append((gamma, rgoal, lb, ub))

    return interpolated_params


def print_progress(iteration, total, params, result, best_so_far, bayesian_mode=False, interpolation_mode=False):
    """
    Print progress update during optimization.

    Args:
        iteration: Current iteration number
        total: Total number of iterations
        params: Current parameters (tuple)
        result: Current result (dict or None)
        best_so_far: Best result found so far (dict or None)
        bayesian_mode: Whether Bayesian optimization is being used
        interpolation_mode: Whether Interpolation mode is being used
    """
    gamma, rgoal, lb, ub = params

    # ANSI color codes
    GREEN = '\033[92m'
    RESET = '\033[0m'

    # Condensed output for random search mode with successful results
    if not (bayesian_mode or interpolation_mode) and result and result['sharpe_ratio'] >= MIN_SHARPE_RATIO:
        print(f"{GREEN}[{iteration}/{total}] γ={gamma:.2f}, rgoal={rgoal:.4f}, lb={lb:.4f}, ub={ub:.4f}{RESET}")
        dist_str = f", Dist={result['distance_from_production']:.4f}" if result.get('distance_from_production') is not None else ""
        print(f"{GREEN}  ✓ Return={result['expected_return']:.8f} ({result['expected_return']*100:.6f}%), Sharpe={result['sharpe_ratio']:.4f}, Vol={result['volatility']:.4f}, WgtStdDev={result['weights_stdev']:.8f}{dist_str}{RESET}")
        return

    # Standard verbose output for Bayesian/Interpolation mode or failed results
    print(f"\n[{iteration}/{total}] γ={gamma:.2f}, rgoal={rgoal:.4f}, lb={lb:.4f}, ub={ub:.4f}")

    if result:
        print(f"  → Return: {result['expected_return']:.8f} ({result['expected_return']*100:.6f}%)")
        print(f"  → Sharpe: {result['sharpe_ratio']:.4f}")
        print(f"  → Volatility: {result['volatility']:.4f} ({result['volatility']*100:.2f}%)")
        print(f"  → Weights StdDev: {result['weights_stdev']:.8f}")
        if result.get('distance_from_production') is not None:
            print(f"  → Distance from Production: {result['distance_from_production']:.4f}")

        if result['sharpe_ratio'] >= MIN_SHARPE_RATIO:
            print(f"  ✓ Meets Sharpe constraint (≥{MIN_SHARPE_RATIO})")
        else:
            print(f"  ✗ Below Sharpe constraint (<{MIN_SHARPE_RATIO})")

    if best_so_far:
        print(f"  Best: Return={best_so_far['expected_return']:.8f}, Sharpe={best_so_far['sharpe_ratio']:.4f}")


def save_results(new_results, verbose=False):
    """
    Append new results to JSON file with file locking for parallel safety.

    Uses fcntl.flock() to prevent race conditions when multiple processes
    write to the same file concurrently.

    Args:
        new_results: List of new result dictionaries to append
        verbose: If True, print detailed save information
    """
    filepath = Path(__file__).parent / RESULTS_FILE

    # Ensure file exists
    if not filepath.exists():
        filepath.write_text('[]')

    # Open file for reading and writing with exclusive lock
    max_retries = 10
    retry_delay = 0.1  # 100ms

    for attempt in range(max_retries):
        try:
            with open(filepath, 'r+') as f:
                # Acquire exclusive lock (blocks until available)
                fcntl.flock(f.fileno(), fcntl.LOCK_EX)

                try:
                    # Read existing results while holding lock
                    f.seek(0)
                    try:
                        existing_results = json.load(f)
                    except json.JSONDecodeError:
                        # Handle corrupted file
                        existing_results = []

                    # Append new results
                    all_results = existing_results + new_results

                    # Write back to file
                    f.seek(0)
                    f.truncate()
                    json.dump(all_results, f, indent=2)
                    f.flush()  # Ensure data is written

                    if verbose:
                        print(f"\nResults saved to {filepath}")
                        print(f"Total results in file: {len(all_results)} ({len(existing_results)} previous + {len(new_results)} new)")

                    # Success - exit retry loop
                    break

                finally:
                    # Release lock
                    fcntl.flock(f.fileno(), fcntl.LOCK_UN)

        except (IOError, OSError) as e:
            if attempt < max_retries - 1:
                time.sleep(retry_delay)
                continue
            else:
                print(f"ERROR: Failed to save results after {max_retries} attempts: {e}")
                raise


def calculate_score(result):
    """
    Calculate a composite score for a result.

    Rewards: return and sharpe
    Penalizes: volatility and weights_stdev

    Args:
        result: Result dictionary

    Returns:
        float: Composite score
    """
    ret = result['expected_return']
    sharpe = result['sharpe_ratio']
    vol = result['volatility']
    wstd = result.get('weights_stdev', 0.02)  # Default for old results

    # Score = (return * sharpe) / (volatility + weights_stdev * 10)
    # This rewards both return and sharpe, penalizes vol and concentration
    # weights_stdev is scaled by 10 to bring it to similar magnitude as volatility
    score = (ret * sharpe) / (vol + wstd * 10)

    return score


def calculate_grid_coverage(results, bins_per_dim=8):
    """
    Calculate search space coverage using grid-based discretization.

    Divides each parameter dimension into bins and counts how many
    unique bin combinations have been tested.

    Args:
        results: List of result dictionaries
        bins_per_dim: Number of bins per parameter dimension (default: 8)

    Returns:
        dict: Coverage statistics including percentage, tested bins, total bins
    """
    if not results:
        return {
            'coverage_pct': 0.0,
            'tested_bins': 0,
            'total_bins': bins_per_dim ** 4,
            'bins_per_dim': bins_per_dim
        }

    # Track unique bin combinations using a set
    tested_bins = set()

    for r in results:
        params = r['params']

        # Calculate bin index for each parameter (0 to bins_per_dim-1)
        gamma_bin = min(
            int((params['gamma'] - PARAM_RANGES['gamma']['min']) /
                (PARAM_RANGES['gamma']['max'] - PARAM_RANGES['gamma']['min']) * bins_per_dim),
            bins_per_dim - 1
        )
        rgoal_bin = min(
            int((params['rgoal'] - PARAM_RANGES['rgoal']['min']) /
                (PARAM_RANGES['rgoal']['max'] - PARAM_RANGES['rgoal']['min']) * bins_per_dim),
            bins_per_dim - 1
        )
        lb_bin = min(
            int((params['lb'] - PARAM_RANGES['lb']['min']) /
                (PARAM_RANGES['lb']['max'] - PARAM_RANGES['lb']['min']) * bins_per_dim),
            bins_per_dim - 1
        )
        ub_bin = min(
            int((params['ub'] - PARAM_RANGES['ub']['min']) /
                (PARAM_RANGES['ub']['max'] - PARAM_RANGES['ub']['min']) * bins_per_dim),
            bins_per_dim - 1
        )

        # Add this bin combination to the set
        tested_bins.add((gamma_bin, rgoal_bin, lb_bin, ub_bin))

    # Calculate coverage
    total_bins = bins_per_dim ** 4
    coverage_pct = (len(tested_bins) / total_bins) * 100.0

    return {
        'coverage_pct': coverage_pct,
        'tested_bins': len(tested_bins),
        'total_bins': total_bins,
        'bins_per_dim': bins_per_dim
    }


def plot_gamma_vs_wgtstdev(results):
    """
    Create a PNG with two plots using gnuplot:
    1. Bubble chart of gamma vs weights_stdev (bubble size = score)
    2. Histogram of rgoal values

    Args:
        results: List of result dictionaries
    """
    if not results:
        return

    # Create data files for gnuplot
    bubble_data_file = Path(__file__).parent / "plot_bubble_data.dat"
    hist_data_file = Path(__file__).parent / "plot_hist_data.dat"
    output_file = Path(__file__).parent / "/var/www/html/parmsearch.png"

    # Write bubble chart data: gamma, wgtstdev, score
    with open(bubble_data_file, 'w') as f:
        f.write("# gamma wgtstdev score\n")
        for r in results:
            gamma = r['params']['gamma']
            wgtstdev = r.get('weights_stdev', 0.0)
            score = r.get('score', 0.0)
            f.write(f"{gamma} {wgtstdev} {score}\n")

    # Write histogram data: rgoal values and calculate binwidth for 20 bins
    rgoal_values = [r['params']['rgoal'] for r in results]
    with open(hist_data_file, 'w') as f:
        f.write("# rgoal\n")
        for rgoal in rgoal_values:
            f.write(f"{rgoal}\n")

    # Calculate binwidth for exactly 20 bins
    rgoal_min = min(rgoal_values)
    rgoal_max = max(rgoal_values)
    binwidth = (rgoal_max - rgoal_min) / 20.0
    if binwidth == 0:  # Handle case where all values are the same
        binwidth = 0.001

    # Create gnuplot script with multiplot (2 panels)
    gnuplot_script = f"""
set terminal pngcairo size 1400,1000 enhanced font 'Helvetica,12'
set output '{output_file}'

# Set up 2x1 multiplot layout
set multiplot layout 2,1 title "Portfolio Optimization Results" font 'Helvetica,14'

# Top panel: Bubble chart
set title 'Gamma vs Weights StdDev - Bubble Chart (size = Score)'
set xlabel 'Gamma (Regularization Parameter)'
set ylabel 'Weights StdDev (Portfolio Concentration)'
set xrange [*:*]
set grid
set palette defined (0 "blue", 0.5 "yellow", 1 "red")
set colorbox
set cblabel 'Score'
set style fill transparent solid 0.4 noborder
plot '{bubble_data_file}' using 1:2:($3*0.044):3 with circles lc palette notitle

# Bottom panel: Histogram of rgoal values
set title 'Distribution of Rgoal (Target Volatility) Values (20 bins)'
set xlabel 'Rgoal (Target Volatility)'
set ylabel 'Frequency'
set style fill solid 0.5 border
set boxwidth {binwidth}
unset colorbox
set autoscale x
binwidth = {binwidth}
bin(x,width) = width*floor(x/width) + width/2.0
plot '{hist_data_file}' using (bin(column(1),binwidth)):(1.0) smooth freq with boxes lc rgb "steelblue" notitle

unset multiplot
"""

    # Run gnuplot
    try:
        result = subprocess.run(
            ['gnuplot'],
            input=gnuplot_script,
            text=True,
            capture_output=True,
            timeout=10
        )

        if result.returncode == 0:
            print(f"\nPlot saved to: {output_file}")
        else:
            print(f"\nWarning: gnuplot failed: {result.stderr}")

    except FileNotFoundError:
        print("\nWarning: gnuplot not found. Install with: apt-get install gnuplot")
    except Exception as e:
        print(f"\nWarning: Failed to generate plot: {e}")

    # Clean up data files
    try:
        bubble_data_file.unlink()
        hist_data_file.unlink()
    except:
        pass


def display_results_report():
    """
    Display a tabular report of all results from the JSON file.
    """
    filepath = Path(__file__).parent / RESULTS_FILE

    if not filepath.exists():
        print(f"Error: Results file not found: {filepath}")
        print("Run the optimizer first to generate results.")
        return

    # Read with shared lock for parallel safety
    with open(filepath, 'r') as f:
        fcntl.flock(f.fileno(), fcntl.LOCK_SH)
        try:
            results = json.load(f)
        finally:
            fcntl.flock(f.fileno(), fcntl.LOCK_UN)

    if not results:
        print("No results found in file.")
        return

    # Load production params and calculate distances for old results
    production_params = parse_currentmodel()
    for r in results:
        if 'distance_from_production' not in r and production_params:
            r['distance_from_production'] = calculate_distance_from_production(
                r['params'], production_params
            )

    # Separate valid and invalid results
    valid_results = [r for r in results if r['sharpe_ratio'] >= MIN_SHARPE_RATIO]
    invalid_results = [r for r in results if r['sharpe_ratio'] < MIN_SHARPE_RATIO]

    # Calculate scores for valid results
    for r in valid_results:
        r['score'] = calculate_score(r)

    # Sort by score (descending)
    valid_results.sort(key=lambda x: x['score'], reverse=True)

    print("=" * 165)
    print("PORTFOLIO OPTIMIZATION RESULTS")
    print("=" * 165)
    print(f"Total results: {len(results)}")
    print(f"Valid results (Sharpe ≥ {MIN_SHARPE_RATIO}): {len(valid_results)}")
    print(f"Invalid results (Sharpe < {MIN_SHARPE_RATIO}): {len(invalid_results)}")
    print()

    # Calculate and display search space coverage
    coverage_stats = calculate_grid_coverage(results, bins_per_dim=args.bins)
    print(f"SEARCH SPACE COVERAGE (Grid-based with {args.bins} bins per dimension):")
    print(f"  Total possible bins:     {coverage_stats['total_bins']:,} ({args.bins}^4)")
    print(f"  Tested unique bins:      {coverage_stats['tested_bins']:,}")
    print(f"  Coverage:                {coverage_stats['coverage_pct']:.2f}%")
    print()

    if production_params:
        print("Production Model (from currentmodel):")
        print(f"  gamma:  {production_params['gamma']:.4f}")
        print(f"  rgoal:  {production_params['rgoal']:.4f}")
        print(f"  lb:     {production_params['lb']:.5f}")
        print(f"  ub:     {production_params['ub']:.4f}")
        print()

    print("Score Formula: (Return × Sharpe) / (Volatility + WgtStdDev × 10)")
    print("  - Rewards: Higher return and Sharpe ratio")
    print("  - Penalizes: Higher volatility and portfolio concentration")
    print("Distance: Normalized Euclidean distance from production parameters (0 = identical)")

    # Calculate and display parameter ranges for valid results
    if valid_results:
        print()
        print("-" * 165)
        print("PARAMETER RANGES (from valid results)")
        print("-" * 165)

        # Extract all parameter values
        gammas = [r['params']['gamma'] for r in valid_results]
        rgoals = [r['params']['rgoal'] for r in valid_results]
        lbs = [r['params']['lb'] for r in valid_results]
        ubs = [r['params']['ub'] for r in valid_results]

        # Display ranges
        print(f"{'Parameter':<12} {'Min':>12} {'Max':>12} {'Range':>12} {'Mean':>12} {'StdDev':>12}")
        print("-" * 165)

        import statistics
        for param_name, values in [('gamma', gammas), ('rgoal', rgoals), ('lb', lbs), ('ub', ubs)]:
            min_val = min(values)
            max_val = max(values)
            range_val = max_val - min_val
            mean_val = statistics.mean(values)
            stdev_val = statistics.stdev(values) if len(values) > 1 else 0.0

            print(f"{param_name:<12} {min_val:>12.6f} {max_val:>12.6f} {range_val:>12.6f} {mean_val:>12.6f} {stdev_val:>12.6f}")

        print("-" * 165)

    if valid_results:
        print("\n" + "-" * 165)
        print("VALID RESULTS (sorted by score)")
        print("-" * 165)
        print(f"{'#':<4} {'Score':>10} {'Return':>12} {'Sharpe':>8} {'Volatility':>10} {'WgtStdDev':>14} {'ProdDist':>10} {'Gamma':>8} {'Rgoal':>8} {'LB':>8} {'UB':>8} {'Timestamp':<20}")
        print("-" * 165)

        for i, r in enumerate(valid_results, 1):
            params = r['params']
            timestamp = r['timestamp'].split('T')[0] + ' ' + r['timestamp'].split('T')[1][:8]
            weights_stdev = r.get('weights_stdev', 0.0)  # Default to 0 for old results
            score = r.get('score', 0.0)
            distance = r.get('distance_from_production')
            dist_str = f"{distance:>10.4f}" if distance is not None else f"{'N/A':>10}"
            print(f"{i:<4} {score:>9.6f} {r['expected_return']:>12.8f} {r['sharpe_ratio']:>8.4f} {r['volatility']:>10.4f} "
                  f"{weights_stdev:>14.8f} {dist_str} {params['gamma']:>8.4f} {params['rgoal']:>8.4f} {params['lb']:>8.4f} {params['ub']:>8.4f} {timestamp:<20}")

        print("-" * 165)
        print("\nBEST RESULT (highest score):")
        best = valid_results[0]
        print(f"  Score:                      {best.get('score', 0.0):.6f}")
        print(f"  Expected Return:            {best['expected_return']:.8f} ({best['expected_return']*100:.6f}%)")
        print(f"  Sharpe Ratio:               {best['sharpe_ratio']:.4f}")
        print(f"  Volatility:                 {best['volatility']:.4f} ({best['volatility']*100:.2f}%)")
        print(f"  Weights StdDev:             {best.get('weights_stdev', 0.0):.8f}")
        if best.get('distance_from_production') is not None:
            print(f"  Distance from Production:   {best['distance_from_production']:.4f}")
        print(f"  Parameters:                 γ={best['params']['gamma']:.4f}, rgoal={best['params']['rgoal']:.4f}, "
              f"lb={best['params']['lb']:.4f}, ub={best['params']['ub']:.4f}")

    if invalid_results and len(invalid_results) <= 20:
        print("\n" + "-" * 120)
        print("INVALID RESULTS (Sharpe < {})".format(MIN_SHARPE_RATIO))
        print("-" * 120)
        print(f"{'#':<4} {'Return':>12} {'Sharpe':>8} {'Volatility':>10} {'Gamma':>8} {'Rgoal':>8} {'LB':>8} {'UB':>8}")
        print("-" * 120)

        for i, r in enumerate(invalid_results, 1):
            params = r['params']
            print(f"{i:<4} {r['expected_return']:>11.8f} {r['sharpe_ratio']:>8.4f} {r['volatility']:>10.4f} "
                  f"{params['gamma']:>8.4f} {params['rgoal']:>8.4f} {params['lb']:>8.4f} {params['ub']:>8.4f}")
        print("-" * 120)
    elif invalid_results:
        print(f"\n({len(invalid_results)} invalid results omitted from display)")

    print("=" * 165)

    # Display plot
    if valid_results:
        plot_gamma_vs_wgtstdev(valid_results)


def display_proximity_report(num_runs=10):
    """
    Display top 3 results that improve on current production return
    with the smallest parameter distance.

    Args:
        num_runs: Number of recent production runs to average (default: 10)
    """
    filepath = Path(__file__).parent / RESULTS_FILE

    if not filepath.exists():
        print(f"Error: Results file not found: {filepath}")
        print("Run the optimizer first to generate results.")
        return

    # Read with shared lock for parallel safety
    with open(filepath, 'r') as f:
        fcntl.flock(f.fileno(), fcntl.LOCK_SH)
        try:
            results = json.load(f)
        finally:
            fcntl.flock(f.fileno(), fcntl.LOCK_UN)

    if not results:
        print("No results found in file.")
        return

    # Load production params and calculate distances for old results
    production_params = parse_currentmodel()
    production_return = parse_production_return(num_runs)

    if not production_params:
        print("Error: Could not parse currentmodel file")
        return

    if production_return is None:
        print("Error: Could not parse modelrun.log for current production return")
        return

    # Calculate distances for results that don't have them
    for r in results:
        if 'distance_from_production' not in r and production_params:
            r['distance_from_production'] = calculate_distance_from_production(
                r['params'], production_params
            )

    # Filter for valid results that improve on production return
    improved_results = [
        r for r in results
        if r['sharpe_ratio'] >= MIN_SHARPE_RATIO
        and r['expected_return'] > production_return
        and r.get('distance_from_production') is not None
    ]

    if not improved_results:
        print("=" * 120)
        print("PROXIMITY REPORT: Top 3 Closest Improvements")
        print("=" * 120)
        print(f"Production Return (avg of last {num_runs} runs):  {production_return:.8f} ({production_return*100:.6f}%)")
        print(f"Production Params:                               γ={production_params['gamma']:.4f}, rgoal={production_params['rgoal']:.4f}, "
              f"lb={production_params['lb']:.5f}, ub={production_params['ub']:.4f}")
        print()
        print("No optimization results found that both:")
        print(f"  1. Improve on production return ({production_return:.8f})")
        print(f"  2. Meet Sharpe ratio constraint (≥{MIN_SHARPE_RATIO})")
        print("=" * 120)
        return

    # Sort by distance (ascending) - closest first
    improved_results.sort(key=lambda x: x['distance_from_production'])

    # Take top 3
    top_3 = improved_results[:3]

    print("=" * 120)
    print("PROXIMITY REPORT: Top 3 Closest Improvements")
    print("=" * 120)
    print(f"Production Return (avg of last {num_runs} runs):  {production_return:.8f} ({production_return*100:.6f}%)")
    print(f"Production Params:                               γ={production_params['gamma']:.4f}, rgoal={production_params['rgoal']:.4f}, "
          f"lb={production_params['lb']:.5f}, ub={production_params['ub']:.4f}")
    print()
    print(f"Found {len(improved_results)} results that improve on production return")
    print("Showing top 3 with smallest parameter distance:")
    print("=" * 120)

    for i, r in enumerate(top_3, 1):
        params = r['params']
        improvement = r['expected_return'] - production_return
        improvement_pct = (improvement / production_return) * 100

        print(f"\n#{i} - Distance: {r['distance_from_production']:.4f}")
        print("-" * 120)
        print(f"  Expected Return:       {r['expected_return']:.8f} ({r['expected_return']*100:.6f}%)")
        print(f"  Improvement:           +{improvement:.8f} (+{improvement_pct:.4f}%)")
        print(f"  Sharpe Ratio:          {r['sharpe_ratio']:.4f}")
        print(f"  Volatility:            {r['volatility']:.4f} ({r['volatility']*100:.2f}%)")
        print(f"  Weights StdDev:        {r.get('weights_stdev', 0.0):.8f}")
        print(f"  Score:                 {r.get('score', 0.0):.6f}")
        print()
        print(f"  Parameters:")
        print(f"    gamma:  {params['gamma']:.4f}  (production: {production_params['gamma']:.4f}, Δ={params['gamma']-production_params['gamma']:+.4f})")
        print(f"    rgoal:  {params['rgoal']:.4f}  (production: {production_params['rgoal']:.4f}, Δ={params['rgoal']-production_params['rgoal']:+.4f})")
        print(f"    lb:     {params['lb']:.5f}  (production: {production_params['lb']:.5f}, Δ={params['lb']-production_params['lb']:+.6f})")
        print(f"    ub:     {params['ub']:.4f}  (production: {production_params['ub']:.4f}, Δ={params['ub']-production_params['ub']:+.4f})")

        timestamp = r['timestamp'].split('T')[0] + ' ' + r['timestamp'].split('T')[1][:8]
        print(f"  Timestamp:             {timestamp}")

    print("\n" + "=" * 120)


def main():
    """
    Main optimization loop.
    """
    # Check if report mode is requested
    if args.report:
        display_results_report()
        return

    # Check if proximity report is requested
    if args.proximity:
        display_proximity_report(num_runs=args.avg_runs)
        return

    # Check for conflicting modes
    if args.interpolate and args.bayesian:
        print("Error: Cannot use both -i (interpolate) and -b (bayesian) modes together")
        print("Choose one search method.")
        return

    # Determine search method
    if args.interpolate:
        search_method = "Interpolation Search"
    elif args.bayesian:
        search_method = "Bayesian Search"
    else:
        search_method = "Random Search"

    # Load production model parameters
    production_params = parse_currentmodel()

    print("=" * 80)
    print(f"Portfolio Parameter Optimization - {search_method}")
    print("=" * 80)
    print(f"Goal: Maximize expected return with Sharpe ratio ≥ {MIN_SHARPE_RATIO}")
    print(f"Iterations: {NUM_ITERATIONS}")
    print(f"Config file: {CONFIG_FILE}")
    print(f"Mode: No-I/O (using existing pricedataset.csv)")
    print()
    print("Parameter Ranges:")
    for param, ranges in PARAM_RANGES.items():
        print(f"  {param:8s}: [{ranges['min']:.4f}, {ranges['max']:.4f}]")
    print()

    if production_params:
        print("Production Model (from currentmodel):")
        print(f"  gamma:  {production_params['gamma']:.4f}")
        print(f"  rgoal:  {production_params['rgoal']:.4f}")
        print(f"  lb:     {production_params['lb']:.5f}")
        print(f"  ub:     {production_params['ub']:.4f}")
        print()

    # Load existing results for Bayesian optimization or Interpolation
    existing_results = []
    interpolated_params_list = []

    if args.bayesian:
        existing_results = load_existing_results()
        if not BAYESIAN_AVAILABLE:
            print("ERROR: Bayesian optimization requires scikit-learn and scipy")
            print("Install with: pip install scikit-learn scipy")
            return
        print(f"Loaded {len(existing_results)} existing results for Bayesian guidance")
        print()
    elif args.interpolate:
        existing_results = load_existing_results()
        if len(existing_results) < 2:
            print(f"ERROR: Interpolation mode requires at least 2 existing results")
            print(f"Found {len(existing_results)} results in {RESULTS_FILE}")
            print("Run some random searches first to build up a result set.")
            return
        interpolated_params_list = generate_interpolated_params_list(existing_results)
        if not interpolated_params_list:
            print("ERROR: Failed to generate interpolated parameters")
            return
        # In interpolation mode, ignore -n flag and use the generated list size
        print(f"Will test {len(interpolated_params_list)} interpolated parameter sets")
        print()

    results = []
    best_result = None

    # Timing statistics (for Bayesian mode)
    iteration_times = []
    start_time = time.time()

    # Determine iteration count
    if args.interpolate:
        num_iterations = len(interpolated_params_list)
    else:
        num_iterations = NUM_ITERATIONS

    for i in range(1, num_iterations + 1):
        iteration_start = time.time()

        # Generate parameters using selected method
        if args.interpolate:
            # Use pre-generated interpolated parameters
            params = interpolated_params_list[i - 1]
        elif args.bayesian:
            # Combine existing results with new results from this run
            all_results = existing_results + results
            params = generate_bayesian_params(all_results)
        else:
            params = generate_random_params()

        gamma, rgoal, lb, ub = params

        # Run optimization
        result = run_optimization(gamma, rgoal, lb, ub, production_params)

        if result:
            # Calculate score for new result
            result['score'] = calculate_score(result)
            results.append(result)

            # Save result immediately (incremental save)
            save_results([result])

            # Track best result that meets Sharpe constraint
            if result['sharpe_ratio'] >= MIN_SHARPE_RATIO:
                if best_result is None or result['expected_return'] > best_result['expected_return']:
                    best_result = result

        # Print progress
        print_progress(i, num_iterations, params, result, best_result,
                      bayesian_mode=args.bayesian, interpolation_mode=args.interpolate)

        # Track iteration time (for Bayesian mode)
        iteration_time = time.time() - iteration_start
        iteration_times.append(iteration_time)

    # Print final summary
    print("\n" + "=" * 80)
    print("OPTIMIZATION COMPLETE")
    print("=" * 80)

    valid_results = [r for r in results if r['sharpe_ratio'] >= MIN_SHARPE_RATIO]

    print(f"\nTotal runs: {len(results)}")
    print(f"Valid results (Sharpe ≥ {MIN_SHARPE_RATIO}): {len(valid_results)}")

    if best_result:
        print("\n" + "-" * 80)
        print("BEST RESULT:")
        print("-" * 80)
        print(f"Expected Return:            {best_result['expected_return']:.8f} ({best_result['expected_return']*100:.6f}%)")
        print(f"Sharpe Ratio:               {best_result['sharpe_ratio']:.4f}")
        print(f"Volatility:                 {best_result['volatility']:.4f} ({best_result['volatility']*100:.2f}%)")
        print(f"Weights StdDev:             {best_result['weights_stdev']:.8f}")
        if best_result.get('distance_from_production') is not None:
            print(f"Distance from Production:   {best_result['distance_from_production']:.4f}")
        print("\nParameters:")
        print(f"  gamma:  {best_result['params']['gamma']:.4f}")
        print(f"  rgoal:  {best_result['params']['rgoal']:.4f}")
        print(f"  lb:     {best_result['params']['lb']:.4f}")
        print(f"  ub:     {best_result['params']['ub']:.4f}")
        print("-" * 80)
    else:
        print("\nNo valid results found meeting Sharpe constraint.")

    # Display computation time statistics (Bayesian mode only)
    if args.bayesian and iteration_times:
        total_time = time.time() - start_time
        avg_time = sum(iteration_times) / len(iteration_times)
        min_time = min(iteration_times)
        max_time = max(iteration_times)
        median_time = sorted(iteration_times)[len(iteration_times) // 2]

        print("\n" + "=" * 80)
        print("COMPUTATION TIME STATISTICS (Bayesian Mode)")
        print("=" * 80)
        print(f"Total elapsed time:         {total_time:>10.2f} seconds ({total_time/60:>6.2f} minutes)")
        print(f"Number of iterations:       {len(iteration_times):>10d}")
        print(f"Average time per iteration: {avg_time:>10.2f} seconds")
        print(f"Median time per iteration:  {median_time:>10.2f} seconds")
        print(f"Min iteration time:         {min_time:>10.2f} seconds")
        print(f"Max iteration time:         {max_time:>10.2f} seconds")

        # Calculate throughput
        throughput = len(iteration_times) / total_time * 3600  # iterations per hour
        print(f"Throughput:                 {throughput:>10.2f} iterations/hour")

        # Estimate time for larger runs
        if len(iteration_times) < 1000:
            print("\nEstimated time for common run sizes:")
            for n in [100, 500, 1000]:
                if n > len(iteration_times):
                    est_time = avg_time * n
                    print(f"  {n:>4d} iterations: ~{est_time/60:>6.1f} minutes ({est_time/3600:>4.2f} hours)")
        print("=" * 80)

    # Results already saved incrementally during iterations
    if results:
        filepath = Path(__file__).parent / RESULTS_FILE
        print(f"\nAll {len(results)} results saved to: {filepath}")

    print("\n")


if __name__ == "__main__":
    main()
