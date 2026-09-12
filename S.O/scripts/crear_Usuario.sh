#!/bin/bash

LOG="/home/uits/gestion_Usuarios/gestion_usuarios.log"

echo "Ingresando al Script de Creacion de usuarios"
read -p "Ingrese Usuario: " usuario

echo "Grupo de arquitectura del sistema:"
echo "1 - admin (root / superusuario del sistema)"
echo "2 - dba (administrador de base de datos)"
echo "3 - backend_api (cuenta de servicio de la aplicacion)"
echo "4 - auditoria (usuario de monitoreo, solo lectura)"
read -p "Seleccione grupo: " opcion_grupo

case $opcion_grupo in
1) grupo="admin";;
2) grupo="dba";;
3) grupo="backend_api";;
4) grupo="auditoria";;
*)
    echo "Opcion invalida. Volviendo al menú principal..."
    sleep 2
    ./menu.sh
    exit 1
;;
esac

if ! getent group "$grupo" &>/dev/null; then
    sudo groupadd "$grupo"
fi

read -p "Necesita acceso a terminal/shell? (s/n): " terminal

if [[ "$terminal" =~ ^[sS]$ ]]; then
    shell="/bin/bash"
else
    shell="/sbin/nologin"
fi

if sudo useradd -r -m -G "$grupo" -s "$shell" "$usuario"; then

    echo "Usuario creado correctamente."
    echo "$(date) - Usuario de sistema $usuario creado correctamente en grupo $grupo" | sudo tee -a "$LOG"

    sudo passwd "$usuario"

else
    echo "Error al crear el usuario."
    echo "$(date) - Error al crear usuario $usuario" | sudo tee -a "$LOG"
fi

tail -n 10 /etc/passwd
./menu.sh
