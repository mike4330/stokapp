#!/usr/bin/python3
import pandas as pd
import numpy as np
from sqlalchemy import create_engine
from sklearn.preprocessing import StandardScaler

# Add debug flag at the top level
DEBUG = False

def debug_print(*args, **kwargs):
    if DEBUG:
        print(*args, **kwargs)

def plot_z_scores(data, width=120, height=20):
    """
    Creates an ASCII plot of z-scores by symbol with horizontal labels
    """
    # Sort data by z_score for better visualization
    sorted_data = data.sort_values('z_score')
    z_scores = sorted_data['z_score'].values
    symbols = sorted_data['symbol'].values
    
    # Find value ranges
    z_min, z_max = min(z_scores), max(z_scores)
    
    # Create the plot array
    plot = [[' ' for _ in range(width)] for _ in range(height)]
    
    # Calculate x positions for each symbol
    x_positions = np.linspace(0, width-1, len(symbols)).astype(int)
    
    # Plot the points
    for i, (z_score, symbol) in enumerate(zip(z_scores, symbols)):
        # Convert z_score to plot coordinates
        plot_y = int((height - 6) * (z_score - z_min) / (z_max - z_min))  # Leave more room for labels
        plot_y = min(max(plot_y, 0), height - 7)
        x_pos = x_positions[i]
        
        # Plot point
        plot[height - 6 - plot_y][x_pos] = '•'
    
    # Add y-axis labels
    y_label_positions = [0, height//2, height-6]
    y_labels = [f'{z_max:6.2f}', f'{(z_max+z_min)/2:6.2f}', f'{z_min:6.2f}']
    
    for pos, label in zip(y_label_positions, y_labels):
        label_chars = list(label)
        for i, char in enumerate(label_chars):
            if i < 6:
                plot[pos][i] = char
    
    # Print the plot
    print("\nZ-Score Distribution by Symbol:")
    print('-' * width)
    print('\n'.join(''.join(row) for row in plot))
    
    # Add x-axis labels (symbols) in two staggered rows to prevent overlap
    x_labels_row1 = [''] * width
    x_labels_row2 = [''] * width
    for i, (pos, symbol) in enumerate(zip(x_positions, symbols)):
        if i % 2 == 0:
            x_labels_row1[pos] = symbol[:4]  # Use first 4 characters of symbol
        else:
            x_labels_row2[pos] = symbol[:4]
    
    print(''.join(' ' if label == '' else f'{label:4}' for label in x_labels_row1))
    print(''.join(' ' if label == '' else f'{label:4}' for label in x_labels_row2))
    print('-' * width)

def calculate_z_score(row, scaler):
    raw_components = {
        "RSI": row["RSI"],
        'PE_diff': row['pe'] - row['average_pe'],
        "volat": row["volat"],
        "mean50": (row["price"] - row["mean50"]) / row["price"],
        "mean200": (row["price"] - row["mean200"]) / row["price"],
        "divyield": row["divyield"],
        "div_growth_rate": row["div_growth_rate"],
        "fcf_ni_ratio": row["fcf_ni_ratio"],
    }

    components_df = pd.DataFrame([raw_components])
    scaled_components = scaler.transform(components_df)
    scaled_components_dict = dict(zip(raw_components.keys(), scaled_components[0]))

    weights = {
        "RSI": 1.1,
        "PE_diff": 1.0,
        "volat": 0.8,
        "mean50": 0.9,
        "mean200": 1.2,
        "divyield": -1.2,
        "div_growth_rate": -0.7,
        "fcf_ni_ratio": -1.2,
    }

    weighted_components = {k: v * weights[k] for k, v in scaled_components_dict.items()}
    z_score = sum(weighted_components.values())

    return z_score, weighted_components

def run_trading_algorithm(data, overweight_min_thresh):
    debug_print("Starting trading algorithm calculations...")
    
    scaler = StandardScaler()
    components_to_scale = [
        "RSI", "PE_diff", "volat", "mean50", "mean200",
        "divyield", "div_growth_rate", "fcf_ni_ratio",
    ]
    
    data_for_scaling = data[components_to_scale].fillna(data[components_to_scale].mean())
    debug_print(f"Scaling {len(components_to_scale)} components...")
    
    scaler.fit(data_for_scaling)

    debug_print("Calculating z-scores...")
    data["z_score"], data["z_components"] = zip(
        *data.apply(lambda row: calculate_z_score(row, scaler), axis=1)
    )

    filtered_data = data[data["overamt"] < overweight_min_thresh]
    debug_print(f"Filtered to {len(filtered_data)} stocks below overweight threshold...")
    
    top_15 = filtered_data.nsmallest(15, "z_score")
    debug_print(f"Selected top {len(top_15)} stocks by z-score")

    return top_15

def analyze_component_influence(results):
    debug_print("Analyzing component influence...")
    all_components = pd.DataFrame(results["z_components"].tolist(), index=results.index)
    avg_influence = all_components.abs().mean().sort_values(ascending=False)
    total_influence = avg_influence.sum()
    percentage_influence = (avg_influence / total_influence * 100).round(2)
    return percentage_influence

def validate_data_types(df):
    debug_print("Starting data validation...")
    numeric_columns = [
        "price", "overamt", "divyield", "fcf_ni_ratio", "volat",
        "RSI", "mean50", "mean200", "div_growth_rate", "pe", 
        "average_pe", "PE_diff"
    ]
    
    issues = []
    
    for col in numeric_columns:
        if col not in df.columns:
            issues.append(f"Missing column: {col}")
            continue
            
        non_numeric_mask = pd.to_numeric(df[col], errors='coerce').isna() & df[col].notna()
        non_numeric_values = df[col][non_numeric_mask]
        
        if len(non_numeric_values) > 0:
            issues.append(f"\nColumn '{col}' contains non-numeric values:")
            for idx, val in non_numeric_values.items():
                issues.append(f"  Row {idx}: '{val}' (Symbol: {df.loc[idx, 'symbol']})")
    
        null_mask = df[col].isna()
        null_count = null_mask.sum()
        if null_count > 0:
            issues.append(f"\nColumn '{col}' contains {null_count} null values:")
            for idx in df[null_mask].index:
                issues.append(f"  Row {idx}: NULL (Symbol: {df.loc[idx, 'symbol']})")

    issues.append("\nCurrent column dtypes:")
    for col in numeric_columns:
        if col in df.columns:
            issues.append(f"  {col}: {df[col].dtype}")
    
    return "\n".join(issues)

def main():
    global DEBUG  # Access the global debug flag
    DEBUG = False  # Set to True to enable debug output
    
    debug_print("Starting main execution...")
    engine = create_engine("sqlite:///../portfolio.sqlite")
    data = pd.read_sql(
        "SELECT prices.symbol,prices.price,sectorshort,overamt,prices.divyield,\
        fcf_ni_ratio,volat,RSI,mean50,mean200,div_growth_rate,pe,average_pe,(pe-average_pe) as PE_diff \
        FROM prices,MPT,sectors\
        where prices.symbol = MPT.symbol and sectors.symbol = MPT.symbol",
        engine,
    )

    if DEBUG:
        print("Validating data types...")
        validation_report = validate_data_types(data)
        print(validation_report)

    data = data.dropna(how='any')
    data = data.fillna(0)
    debug_print(f"Data shape after cleaning: {data.shape}")

    overweight_min_thresh = -6
    results = run_trading_algorithm(data, overweight_min_thresh)
    
    # Plot z-scores for all data before filtering
    plot_z_scores(data)
    
    # Always show the top 15 results table
    print("\nTop 15 Results:")
    pd.set_option('display.max_rows', None)
    print(results[["symbol", "sectorshort", "z_score", "overamt"]].to_string())
    
    if DEBUG:
        influence = analyze_component_influence(results)
        print("\nComponent Influence (%):")
        print(influence)

        top_symbol = results.iloc[0]
        print(f"\nComponents for top symbol {top_symbol['symbol']}:")
        for component, value in top_symbol["z_components"].items():
            print(f"{component}: {value:.4f}")
    
    return results

if __name__ == "__main__":
    main()