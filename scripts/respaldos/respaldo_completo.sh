#!/bin/bash
FECHA=$(date +%Y%m%d)
DESTINO="/home/respaldos"
ORIGEN="/"

tar -cvzf "$DESTINO/full_$FECHA.tar.gz" \
    --listed-incremental="$DESTINO/full.snar" \
    "$ORIGEN"
