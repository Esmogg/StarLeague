#!/bin/bash

clear

echo "======================================"
echo "       GESTION DE USUARIOS"
echo "======================================"
echo "1- Crear usuario"
echo "2- Leer usuario"
echo "3- Modificar usuario"
echo "4- Eliminar usuario"
echo "5- Salir"
echo ""

read -p "Seleccione una opcion: " opcion

case $opcion in
    1)
        ./crear_Usuario.sh
        ;;
    2)
        ./leer_Usuario.sh
        ;;
    3)
        ./modificar_Usuario.sh
        ;;
    4)
        ./eliminar_Usuario.sh
        ;;
    5)
        echo "Saliendo..."
        ;;
    *)
        echo "Opcion invalida."
        ;;
esac
