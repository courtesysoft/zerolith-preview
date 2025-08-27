#!/bin/sh

# Retrieved 01/09/2025 from https://datagubbe.se/mincss - DS

# mincss - Minimal CSS minifier in POSIX sh.
# Usage: mincss input-file [output-file]
# Usage of the works is permitted provided that this instrument is
# retained with the works, so that any entity that uses the works is
# notified of this instrument.
# DISCLAIMER: THE WORKS ARE WITHOUT WARRANTY.

INFILE=$1
OUTFILE=$2

if [ -z "$INFILE" ]; then
  echo "mincss - Minimal CSS minifier in POSIX sh."
  echo "Usage: mincss input-file [output-file]"
  exit;
fi

OUTPUT=$(\
  # Remove newlines:
  tr "\n" " " < "$INFILE" |\
  # Comments require a lot of steps since sed regexps are greedy.
  # Put comments on separate lines (opening marker /*):
  sed "s|\/\*|\n\/\*|g" |\
  # Put comments on separate lines (closing marker */):
  sed "s|\*\/|\*\/\n|g" |\
  # Remove comments:
  sed "s|\/\*.*\*\/||g" |\
  # Remove newlines left over from comment parsing:
  tr "\n" " " |\
  # Convert tabs to spaces:
  tr "\t" " " |\
  # Trim possible leftover leading spaces:
  sed "s/^ *//" |\
  # Compact spaces:
  sed "s/ \{2,255\}/ /g"\
)

# Trim spaces around significant chars
for CHAR in "{" "}" ":" ";" ","
do
  OUTPUT=$(echo "$OUTPUT" | sed "s/ *$CHAR */$CHAR/g")
done

if [ -z "$OUTFILE" ]; then
  echo "$OUTPUT"
else
  echo "$OUTPUT" > "$OUTFILE"
  ORGSIZE=$(wc -c "$INFILE" | awk '{ print $1 }')
  NEWSIZE=$(wc -c "$OUTFILE" | awk '{ print $1 }')
  SAVEDBYTE=$(echo "$ORGSIZE $NEWSIZE" | awk '{ print $1-$2 }')
  SAVEDPCNT=$(echo "$SAVEDBYTE $ORGSIZE" | awk '{ print ($1/$2)*100 }')
  echo "Trimmed $SAVEDBYTE bytes ($SAVEDPCNT% smaller)"
fi