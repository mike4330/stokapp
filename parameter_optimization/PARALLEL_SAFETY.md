# Parallel Safety Improvements

## Summary

The optimizer is now **safe for parallel execution** using file locking (`fcntl.flock`).

## What Was Fixed

### Before (UNSAFE)
- Multiple processes could read/write the same JSON file simultaneously
- Race conditions caused **data loss** (test showed 75% loss without locking!)
- File corruption possible (partial writes, invalid JSON)
- Incremental saves made the problem worse (more frequent writes = more collisions)

### After (SAFE)
- All file operations use **fcntl.flock()** for synchronization
- **Exclusive locks (LOCK_EX)** for writes - only one writer at a time
- **Shared locks (LOCK_SH)** for reads - multiple readers OK, blocks writers
- Atomic read-modify-write operations
- Automatic retry on transient errors
- Test shows **0% data loss** with locking

## Changes Made

1. **save_results()** - Uses exclusive lock during read-modify-write
2. **load_existing_results()** - Uses shared lock for reads
3. **display_results_report()** - Uses shared lock for reads
4. **display_proximity_report()** - Uses shared lock for reads

## File Locking Behavior

```
Process A (write)     Process B (write)     Process C (read)
=================     =================     ================
Acquire LOCK_EX  →    Wait for lock...      Wait for lock...
Read file             ↓                     ↓
Modify data           ↓                     ↓
Write file            ↓                     ↓
Release lock          ↓                     ↓
                      Acquire LOCK_EX  →    Wait for lock...
                      Read file             ↓
                      Modify data           ↓
                      Write file            ↓
                      Release lock          ↓
                                            Acquire LOCK_SH
                                            Read file
                                            Release lock
```

## Testing

Run the test suite to verify locking works:
```bash
./test_locking.py
```

Expected output:
- **Without locking**: FAIL (75-100% data loss, JSON errors)
- **With locking**: PASS (0% data loss, no errors)

## Usage

### Original Loop Script (still works, now safe)
```bash
./loop 50    # Launch 9 workers, each doing 50 iterations
```

### New Safe Loop Script (recommended)
```bash
./loop_safe 50 9     # Launch 9 workers, 50 iterations each
./loop_safe 100 4    # Launch 4 workers, 100 iterations each
./loop_safe 20       # Launch 9 workers (default), 20 iterations each
```

Features of `loop_safe`:
- Tracks all worker PIDs
- Waits for all workers to complete
- Reports success/failure status
- Creates timestamped backups
- Shows final result count
- Configurable workers and iterations

## Performance Impact

File locking adds minimal overhead:
- Lock acquisition: ~0.1-1ms
- Blocking only during actual I/O (very brief)
- Test showed locking was actually **faster** (better coordination)

## Best Practices

1. **Use loop_safe** instead of the old loop script
2. **Don't run too many workers** - diminishing returns after ~10 workers
3. **Monitor for lock contention** - if workers spend time waiting, reduce parallelism
4. **Backups are automatic** - loop_safe creates timestamped backups before each run
5. **Stagger launches** - loop_safe staggers by 1.8s to reduce initial contention

## Backwards Compatibility

✓ All existing code continues to work
✓ Single-process runs (no -b flag) work unchanged
✓ Results file format unchanged
✓ Old loop script still works (now safe with locking)

## Architecture

```
┌─────────────────────────────────────────────────────┐
│  Worker 1        Worker 2        Worker 3           │
│  ────────        ────────        ────────           │
│     │               │               │               │
│     ▼               ▼               ▼               │
│  save_results()  save_results()  save_results()     │
│     │               │               │               │
│     └───────────────┴───────────────┘               │
│                     │                               │
│                     ▼                               │
│          ┌──────────────────────┐                   │
│          │  fcntl.flock(LOCK_EX)│ ◄─── Serializes  │
│          │  ────────────────────│      all writes   │
│          │  Read existing       │                   │
│          │  Append new results  │                   │
│          │  Write atomically    │                   │
│          │  Release lock        │                   │
│          └──────────────────────┘                   │
│                     │                               │
│                     ▼                               │
│         optimization_results.json                   │
│         (always consistent!)                        │
└─────────────────────────────────────────────────────┘
```

## Troubleshooting

**Q: Workers seem slow**
A: Too many workers may cause lock contention. Try fewer workers with more iterations each.

**Q: File corruption**
A: Should not happen with locking. If it does, please report - may indicate filesystem issue.

**Q: Lost results**
A: Check worker exit codes in loop_safe output. Failed workers won't have saved results.

**Q: Want even more parallelism**
A: Consider running on different machines with separate result files, then merging.
