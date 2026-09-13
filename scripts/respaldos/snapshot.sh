#!/bin/bash

FECHA=$(date +%Y%m%d)
VMID=100

qm snapshot $VMID snapshot_$FECHA --description "Snapshot previo a la actualización"