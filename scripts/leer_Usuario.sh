#!/bin/bash

LOG="/home/uits/gestion_Usuarios/gestion_usuarios.log"

echo "Ingresando al Script de Lectura de Usuarios"
read -p "Ingrese Usuario a consultar (vacio para listar todos): " usuario

if [ -z "$usuario" ]; then
    echo "Listado de usuarios de sistema:"
    awk -F: '$3 >= 1 && $3 < 1000 {print $1" (UID:"$3")"}' /etc/passwd
    echo "$(date) - Consulta de listado de usuarios de sistema" | sudo tee -a "$LOG"
else
    if id "$usuario" &>/dev/null; then
        echo "Informacion de $usuario:"
        id "$usuario"
        getent passwd "$usuario"
        echo "$(date) - Consulta de usuario $usuario" | sudo tee -a "$LOG"
    else
        echo "El usuario no existe"
        echo "$(date) - Consulta fallida, usuario inexistente: $usuario" | sudo tee -a "$LOG"
    fi
fi
