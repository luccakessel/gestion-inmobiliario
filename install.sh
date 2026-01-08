#!/bin/bash

# Script de instalación para Despacho de Abogados
# Ejecutar como: bash install.sh

echo "🏛️ Instalando Sistema de Gestión de Despacho de Abogados..."

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Función para mostrar mensajes
print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Verificar si es root
if [ "$EUID" -eq 0 ]; then
    print_error "No ejecutes este script como root. Usa un usuario con sudo."
    exit 1
fi

# Verificar sistema operativo
if [ -f /etc/os-release ]; then
    . /etc/os-release
    OS=$NAME
    VER=$VERSION_ID
else
    print_error "No se pudo detectar el sistema operativo"
    exit 1
fi

print_status "Sistema detectado: $OS $VER"

# Instalar dependencias según el SO
if [[ "$OS" == *"Ubuntu"* ]] || [[ "$OS" == *"Debian"* ]]; then
    print_status "Instalando dependencias para Ubuntu/Debian..."
    sudo apt update
    sudo apt install -y apache2 mysql-server php php-mysql php-curl php-gd php-mbstring php-xml php-zip php-cli unzip
    
elif [[ "$OS" == *"CentOS"* ]] || [[ "$OS" == *"Red Hat"* ]]; then
    print_status "Instalando dependencias para CentOS/RHEL..."
    sudo yum update -y
    sudo yum install -y httpd mysql-server php php-mysql php-curl php-gd php-mbstring php-xml php-zip unzip
    
else
    print_error "Sistema operativo no soportado: $OS"
    exit 1
fi

# Habilitar y iniciar servicios
print_status "Configurando servicios..."
sudo systemctl enable apache2 2>/dev/null || sudo systemctl enable httpd
sudo systemctl start apache2 2>/dev/null || sudo systemctl start httpd
sudo systemctl enable mysql
sudo systemctl start mysql

# Configurar MySQL
print_status "Configurando MySQL..."
sudo mysql -e "CREATE DATABASE IF NOT EXISTS despacho_abogados;"
sudo mysql -e "CREATE USER IF NOT EXISTS 'despacho_user'@'localhost' IDENTIFIED BY 'Despacho2024!';"
sudo mysql -e "GRANT ALL PRIVILEGES ON despacho_abogados.* TO 'despacho_user'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"

# Crear directorio del proyecto
PROJECT_DIR="/var/www/html/despacho-abogados"
print_status "Creando directorio del proyecto: $PROJECT_DIR"
sudo mkdir -p $PROJECT_DIR
sudo chown -R www-data:www-data $PROJECT_DIR 2>/dev/null || sudo chown -R apache:apache $PROJECT_DIR

# Copiar archivos (asumiendo que el script está en el directorio del proyecto)
print_status "Copiando archivos del sistema..."
sudo cp -r . $PROJECT_DIR/
sudo chown -R www-data:www-data $PROJECT_DIR 2>/dev/null || sudo chown -R apache:apache $PROJECT_DIR

# Configurar permisos
print_status "Configurando permisos..."
sudo chmod 755 -R $PROJECT_DIR/
sudo chmod 777 -R $PROJECT_DIR/uploads/
sudo chmod 644 $PROJECT_DIR/includes/db.php

# Importar base de datos
print_status "Importando base de datos..."
if [ -f "sql/tienda_hockey.sql" ]; then
    sudo mysql -u despacho_user -p'Despacho2024!' despacho_abogados < sql/tienda_hockey.sql
    print_status "Base de datos importada correctamente"
else
    print_warning "Archivo SQL no encontrado. Importa manualmente: sql/tienda_hockey.sql"
fi

# Configurar PHP
print_status "Configurando PHP..."
sudo sed -i 's/upload_max_filesize = .*/upload_max_filesize = 50M/' /etc/php/*/apache2/php.ini 2>/dev/null || sudo sed -i 's/upload_max_filesize = .*/upload_max_filesize = 50M/' /etc/php.ini
sudo sed -i 's/post_max_size = .*/post_max_size = 50M/' /etc/php/*/apache2/php.ini 2>/dev/null || sudo sed -i 's/post_max_size = .*/post_max_size = 50M/' /etc/php.ini

# Reiniciar servicios
print_status "Reiniciando servicios..."
sudo systemctl restart apache2 2>/dev/null || sudo systemctl restart httpd
sudo systemctl restart mysql

# Crear archivo .htaccess
print_status "Creando archivo .htaccess..."
sudo tee $PROJECT_DIR/.htaccess > /dev/null <<EOF
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]

# Proteger archivos sensibles
<Files "includes/db.php">
    Order Allow,Deny
    Deny from all
</Files>

# Proteger directorio de uploads
<Directory "uploads">
    Options -Indexes
</Directory>
EOF

# Mostrar información final
echo ""
echo "🎉 ¡Instalación completada!"
echo ""
echo "📋 Información del sistema:"
echo "   URL: http://$(hostname -I | awk '{print $1}')/despacho-abogados/"
echo "   Usuario: admin"
echo "   Contraseña: admin123"
echo ""
echo "🔐 Credenciales de base de datos:"
echo "   Host: localhost"
echo "   Base de datos: despacho_abogados"
echo "   Usuario: despacho_user"
echo "   Contraseña: Despacho2024!"
echo ""
echo "⚠️  IMPORTANTE:"
echo "   1. Cambia las contraseñas por defecto"
echo "   2. Configura HTTPS para producción"
echo "   3. Haz respaldos regulares de la base de datos"
echo "   4. Configura firewall si es necesario"
echo ""
echo "📁 Archivos del sistema en: $PROJECT_DIR"
echo "📊 Logs de Apache: /var/log/apache2/error.log"
echo ""

print_status "Instalación finalizada. ¡El sistema está listo para usar!"
