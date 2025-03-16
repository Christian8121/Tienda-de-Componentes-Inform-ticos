# Proyecto Tienda de Componentes Informáticos

## Descripción

Este proyecto es una tienda en línea para una empresa de componentes informáticos. Permite a los usuarios navegar por los productos, agregarlos al carrito de compra y realizar pedidos. También incluye una sección de administración donde los administradores pueden gestionar productos y pedidos.

La tienda tiene dos secciones diferenciadas: **Sección de Usuario** y **Sección de Administración**.

## Tecnologías Utilizadas

- **PHP**: Para el desarrollo del backend.
- **MySQL**: Para la base de datos.
- **HTML/CSS**: Para la estructura y el diseño visual de la página.
- **JavaScript**: Para la interactividad en el frontend (por ejemplo, el carrito de compras).
- **Modelo MVC**: Para estructurar el código de manera ordenada y escalable.

## Funcionalidad

### Sección de Usuario

1. **Página Principal**:
   - Listado de productos con imagen, breve descripción y botones de "Añadir al carrito" y "Ver detalles".
   - Carrusel con imágenes destacadas de productos.
   - Barra de búsqueda para encontrar productos.

   ![Página Principal](ruta/a/la/captura-de-pantalla1.png)

2. **Ficha de Producto**:
   - Descripción completa de cada producto al hacer clic en "Ver detalles".

   ![Ficha de Producto](ruta/a/la/captura-de-pantalla2.png)

3. **Carrito de Compra**:
   - Los usuarios pueden añadir productos al carrito.
   - El carrito muestra la cantidad de productos añadidos en el icono.
   - Los usuarios pueden continuar comprando o proceder al checkout.
   - Si un usuario no ha iniciado sesión, los productos se guardan en la sesión, pero se perderán al cerrar el navegador.

   ![Carrito de Compra](ruta/a/la/captura-de-pantalla3.png)

4. **Inicio de Sesión**:
   - Los usuarios pueden iniciar sesión como usuarios normales o como administradores.
   - Los usuarios normales pueden ver su carrito y sus pedidos, pero no pueden gestionar productos.
   - Los administradores acceden a un panel con opciones para gestionar productos y pedidos.

   ![Inicio de Sesión](ruta/a/la/captura-de-pantalla4.png)

### Sección de Administración

1. **Panel de Control**:
   - Acceso con credenciales de administrador.
   - Menú lateral con opciones: **Gestionar Productos**, **Gestionar Pedidos**, **Gestionar Usuarios**.
   - **Gestionar Productos**: Añadir, editar o eliminar productos.
   - **Gestionar Pedidos**: Ver los pedidos realizados por los usuarios.
   - **Gestionar Usuarios**: Crear, editar o eliminar usuarios (solo para administradores).

   ![Panel de Control](ruta/a/la/captura-de-pantalla5.png)

2. **Gestión de Productos**:
   - Los administradores pueden ver el listado de productos con detalles como ID, nombre, categoría, precio y stock.
   - Añadir nuevos productos con nombre, descripción y precio.
   - Editar y eliminar productos existentes.

   ![Gestión de Productos](ruta/a/la/captura-de-pantalla6.png)

## Requisitos

- **PHP 7.4 o superior**
- **MySQL**: Base de datos para almacenar los productos, usuarios y pedidos.
- **Servidor web**: Apache o Nginx.

## Estructura del Proyecto

