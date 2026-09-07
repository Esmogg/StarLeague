#!/bin/bash

clear
echo "=== Ingresando al menu de monitoreo ==="
echo "Desea: "
echo "1- Administrar procesos"
echo "2- Gestionar el firewall"
echo "3- Salir"
read -p "Ingrese una opcion" opcion

case $opcion in
1) ./procesos.sh ;;
2) ./firewall.sh ;;
3) echo "saliendo"
exit 0
;;
*) echo "opcion invalida" 
sleep 3 
./menu_monitoreo.sh
;;
esac
