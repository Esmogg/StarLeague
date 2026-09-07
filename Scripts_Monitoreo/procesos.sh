#!/bin/bash

ver_procesos(){
clear
echo -e "\nProcesos activos del sistema:"
ps aux --sort=-%cpu | head -n 11
}


finalizar_procesos(){
clear

echo -e "Ingresando al menu de Finalizar tareas"
echo -e "\nDesea:"
echo "1- Usar SIGTERM"
echo "2- Usar SIGKILL"

read -p "Seleccione una opcion: " op

echo -e "\nProcesos activos:"
ps aux --sort=-%cpu | head -n 11

read -p "Ingrese el Process ID (PID): " Pid

if ! [[ "$Pid" =~ ^[0-9]+$ ]]; then
    echo "Error: El PID debe ser un numero."
    return
fi

if ! kill -0 "$Pid" 2>/dev/null; then
    echo "Error: El proceso con PID $Pid no existe o no tiene permisos."
    return
fi

if [ "$op" -eq 1 ]; then
    kill -15 "$Pid"
fi

if [ "$op" -eq 2 ]; then
    kill -9 "$Pid"
fi

if [ "$op" -ne 1 ] && [ "$op" -ne 2 ]; then
    echo "Error: Opcion invalida."
    return
fi

if [ $? -eq 0 ]; then
    echo "Proceso $Pid finalizado correctamente."
else
    echo "Error: No se pudo finalizar el proceso $Pid."
fi
}


liberar_cache(){
clear

echo "Se solicita permiso de administrador"

sudo sync

echo 3 | sudo tee /proc/sys/vm/drop_caches > /dev/null

if [ $? -eq 0 ]; then
    echo "Cache liberado!"
else
    echo "Error: No se pudo liberar la cache."
fi
}


ver_memoria(){
clear

echo -e "\nUso de memoria:"
free -h
}


ver_disco(){
clear

echo -e "\nUso del disco:"
df -h
}


usuarios_conectados(){
clear

echo -e "\nUsuarios conectados:"
who
}


informacion_sistema(){
clear

echo -e "\nInformacion del sistema:"
uname -a
}


while true; do

echo -e "\n========== MENU PROCESOS =========="
echo "1- Ver procesos activos (Primeros 10)"
echo "2- Finalizar proceso"
echo "3- Ver uso de memoria"
echo "4- Ver uso del disco"
echo "5- Liberar cache"
echo "6- Usuarios conectados"
echo "7- Informacion del sistema"
echo "8- Salir"
echo "==================================="

read -p "Seleccione una opcion (1-8): " opcion

case $opcion in

1)
    ver_procesos
    ;;

2)
    finalizar_procesos
    ;;

3)
    ver_memoria
    ;;

4)
    ver_disco
    ;;

5)
    liberar_cache
    ;;

6)
    usuarios_conectados
    ;;

7)
    informacion_sistema
    ;;

8)
    echo "Saliendo..."
    exit 0
    ;;

*)
    echo "Error: Opcion invalida"
    ;;

esac
done
