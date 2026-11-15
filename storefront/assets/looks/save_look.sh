#!/usr/bin/env bash
# Usage: bash assets/looks/save_look.sh 03-jacket  (picks ~/Downloads/bh-03-jacket.png)
set -e
cd "$(dirname "$0")/../.."
name="$1"; src="$HOME/Downloads/bh-$name.png"
sleep 1; [ -f "$src" ] || { echo "missing $src"; exit 1; }
mv "$src" "assets/looks/originals/$name.png"
python -I assets/looks/process_looks.py > assets/looks/manifest.json
grep -A5 "\"$name\"" assets/looks/manifest.json
out="${TMPDIR:-/tmp}/check-$name.jpg"
ffmpeg -v error -y -f lavfi -i color=c=0xE8D5C0:s=1024x1536 -i "assets/looks/originals/$name.png" -filter_complex "[0][1]overlay=format=auto,scale=512:-1" -frames:v 1 "$out"
echo "$out"
