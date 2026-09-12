#!/bin/bash

while true; do

clear
echo "==== Administracion del Firewall ===="
echo "1- Administrar puertos"
echo "2- Administrar IP's"
echo "3- Administrar Servicios"
echo "4- Ver Configuracion"
echo "5- Recargar Cambios"
echo "6- Dejar cambios como Permanente"
echo "7- Salir al menu principal"
read -p "Ingrese una opcion: " opc
case $opc in
1) ./ports.sh ;;
2) ./ips.sh ;;
3) ./servicios.sh ;;
4)
clear
firewall-cmd --list-all
read -p "Ingrese un boton para continuar: " boton
;;
5)
firewall-cmd --reload
clear
echo -e "Se ha recargado la configuracion \n"
read -p "Ingrese un boton para continuar: " boton
;;
6)
firewall-cmd --runtime-to-permanent
clear
echo -e "Los cambios hechos han quedado permanentes \n"
;;
7)
echo "Saliendo"
exit 0
;;
*)
exit 1 
;;
esac
done
