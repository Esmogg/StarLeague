#!/bin/bash

LOG="/home/uits/gestion_Usuarios/gestion_usuarios.log"

echo "Ingresando al Script de Eliminacion de Usuarios"
read -p "Ingrese Usuario a eliminar: " usuario

protegidos=("root" "$USER" "Pepe" "daemon" "bin" "sys")
for protegido in "${protegidos[@]}"
do
    if [ "$usuario" = "$protegido" ]; then
    echo "No se puede eliminar el usuario $usuario"
    echo "$(date) - Intento de eliminar usuario protegido: $usuario" | sudo tee -a "$LOG"
    exit 1
fi
done
if sudo userdel -r "$usuario"; then
    echo "Usuario eliminado correctamente"
    echo "$(date) - Usuario $usuario eliminado correctamente" | sudo tee -a "$LOG"
else
    echo "Error al eliminar el usuario"
    echo "$(date) - Error al eliminar usuario $usuario" | sudo tee -a "$LOG"
fi
tail -n 10 /etc/passwd
./menu.sh
