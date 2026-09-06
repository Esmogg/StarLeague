#!/bin/bash

clear
echo "=== Ingresando al menu de monitoreo ==="
echo "Desea: "
echo "1- Administrar procesos"
echo "2- Gestionar servicios"
echo "3- Gestionar el firewall"
echo "4- Salir"
read -p "Ingrese una opcion" opcion

case $opcion in
1) ./procesos.sh ;;
2) ./servicios.sh ;;
3) ./firewall.sh ;;
4) echo "saliendo" ;;
*) echo "opcion invalida" sleep 3 ./menu_monitoreo.sh ;;
esac
