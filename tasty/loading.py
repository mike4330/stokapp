import csv

def load_csv_to_associative_array(filename):

    associative_array = {}
    with open(filename, "r") as csvfile:
        csvreader = csv.reader(csvfile)
        header = next(csvreader)  # Assuming the first row contains headers
        for row in csvreader:
            symbol, stab = row
            associative_array[symbol] = float(stab)
    return associative_array