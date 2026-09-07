#!/bin/bash

while true; do
    clear
    echo "----------------- Configurar IP's -----------------"
    echo "[1] Permitir IP (Agregar regla Accept)"
    echo "[2] Rechazar IP (Agregar regla Reject)"
    echo "[0] Salir"
    echo -e "--------------------------------------------------\n"
    
    read -p "Elija una opcion: " opc

    case $opc in
        1)
            read -p "Ingrese la IP a permitir: " ai
            firewall-cmd --add-rich-rule="rule family='ipv4' source address='$ai' accept"
            clear
            echo "La IP $ai ha sido permitida (accept)."
            read -p "Presione ENTER para continuar... " boton
            ;;
        2)
            read -p "Ingrese la IP a rechazar: " ri
            firewall-cmd --add-rich-rule="rule family='ipv4' source address='$ri' reject"
            clear
            echo "La IP $ri ha sido rechazada (reject)."
            read -p "Presione ENTER para continuar... " boton
            ;;
        0)
            echo "Saliendo..."
            break
            ;;
        *)
            echo "Opcion invalida"
            sleep 2
            ;;
    esac
done
