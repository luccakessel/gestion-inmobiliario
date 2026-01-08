# ✅ Checklist de Despliegue en Hostinger

## 📋 Lista de Verificación Pre-Despliegue

### 🔧 Configuración de Archivos
- [ ] Archivo `includes/db.php` actualizado con datos de Hostinger
- [ ] Archivo `.htaccess` creado y configurado
- [ ] Archivo `security_config.php` incluido
- [ ] Archivo `verificar_hostinger.php` listo para pruebas

### 📁 Archivos a Subir
- [ ] Carpeta `admin/` completa
- [ ] Carpeta `includes/` completa  
- [ ] Carpeta `public/` completa
- [ ] Carpeta `uploads/` (crear en Hostinger)
- [ ] Archivo `index.php`
- [ ] Archivo `.htaccess`
- [ ] Archivo `verificar_hostinger.php`

### 🚫 Archivos NO Subir
- [ ] Carpeta `node_modules/`
- [ ] Archivos `.sql` locales
- [ ] Archivo `config_hostinger.php`
- [ ] Archivo `test_login.php`
- [ ] Archivos de desarrollo local

## 🗄️ Base de Datos

### Configuración en Hostinger
- [ ] Base de datos creada en panel de Hostinger
- [ ] Usuario de BD creado con permisos completos
- [ ] Contraseña segura generada
- [ ] Archivo `sql/hostinger_database.sql` importado en phpMyAdmin

### Verificación de BD
- [ ] Tabla `usuarios` creada correctamente
- [ ] Tabla `clientes` creada correctamente
- [ ] Tabla `casos` creada correctamente
- [ ] Tabla `facturas` creada correctamente
- [ ] Tabla `especialidades` creada correctamente
- [ ] Datos de ejemplo insertados

## ⚙️ Configuración del Sistema

### Datos de Conexión
- [ ] Nombre de BD actualizado en `includes/db.php`
- [ ] Usuario de BD actualizado en `includes/db.php`
- [ ] Contraseña de BD actualizada en `includes/db.php`
- [ ] Host configurado como `localhost`

### Permisos de Carpetas
- [ ] Carpeta `uploads/` con permisos 755
- [ ] Carpeta `uploads/documentos/` con permisos 755
- [ ] Archivos PHP con permisos 644

## 🔐 Seguridad

### Configuración SSL
- [ ] SSL activado en panel de Hostinger
- [ ] Redirección HTTPS configurada (opcional)
- [ ] Headers de seguridad activados

### Archivos Sensibles
- [ ] Archivos de configuración protegidos
- [ ] Logs de error configurados
- [ ] Acceso a phpMyAdmin restringido

## 🧪 Pruebas Post-Despliegue

### Funcionalidades Básicas
- [ ] Acceso a `https://tudominio.com/` funciona
- [ ] Login de administrador (`admin/admin`) funciona
- [ ] Panel de administración carga correctamente
- [ ] Navegación entre secciones funciona

### Gestión de Datos
- [ ] Crear nuevo cliente funciona
- [ ] Crear nueva propiedad funciona
- [ ] Crear factura funciona
- [ ] Subir documentos funciona
- [ ] Generar reportes funciona

### Base de Datos
- [ ] Datos se guardan correctamente
- [ ] Consultas complejas ejecutan sin errores
- [ ] Relaciones entre tablas funcionan
- [ ] Backup automático configurado

## 📊 Verificación Final

### Script de Verificación
- [ ] Ejecutar `verificar_hostinger.php`
- [ ] Todos los elementos muestran ✅
- [ ] No hay errores críticos
- [ ] Sistema listo para producción

### Rendimiento
- [ ] Páginas cargan en menos de 3 segundos
- [ ] No hay errores 500
- [ ] Memoria PHP suficiente
- [ ] Límites de archivo adecuados

## 🚀 Go Live

### Últimos Pasos
- [ ] Cambiar contraseña de administrador
- [ ] Configurar email de notificaciones (opcional)
- [ ] Configurar backup automático
- [ ] Documentar credenciales de acceso

### Monitoreo
- [ ] Verificar logs de error regularmente
- [ ] Monitorear uso de recursos
- [ ] Verificar funcionamiento de SSL
- [ ] Probar funcionalidades críticas

---

## 🆘 En Caso de Problemas

### Errores Comunes
- **Error 500**: Verificar permisos y sintaxis PHP
- **Error de BD**: Verificar credenciales en `includes/db.php`
- **Archivos no suben**: Verificar permisos de carpeta `uploads/`
- **SSL no funciona**: Esperar propagación DNS (hasta 24h)

### Contacto de Soporte
- **Hostinger**: Panel de control → Soporte
- **Documentación**: `GUIA_DESPLIEGUE_HOSTINGER.md`
- **Verificación**: `verificar_hostinger.php`

---

**¡Sistema listo para producción! 🎉**
