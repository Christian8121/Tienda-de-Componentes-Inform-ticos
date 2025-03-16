# Proyecto Tienda de Componentes Informáticos

## Descripción

Este proyecto es una tienda en línea para una empresa de componentes informáticos. Permite a los usuarios navegar por los productos, agregarlos al carrito de compra y realizar pedidos. También incluye una sección de administración donde los administradores pueden gestionar productos, usuarios y pedidos.

La tienda tiene dos secciones diferenciadas: **Sección de Usuario** y **Sección de Administración**.

- **Sección de Usuario**: Los usuarios pueden navegar por los productos, añadirlos al carrito, ver los detalles de cada producto y realizar compras.
- **Sección de Administración**: Los administradores pueden gestionar productos, pedidos y usuarios, y realizar cambios en la tienda, como añadir, editar o eliminar productos.

## Tecnologías Utilizadas

- **PHP**: Para el desarrollo del backend, gestionando la lógica del negocio, la interacción con la base de datos y la autenticación de usuarios.
- **MySQL**: Para almacenar y gestionar los datos de la tienda, incluyendo productos, usuarios y pedidos.
- **HTML/CSS**: Para la estructura y el diseño visual de las páginas web, asegurando una experiencia de usuario atractiva.
- **JavaScript**: Para la interactividad en el frontend, como la funcionalidad del carrito de compras, la validación de formularios y la interacción dinámica con el servidor.
- **Modelo MVC**: Se utiliza para organizar el código en tres capas: Modelo (gestión de datos), Vista (interfaz de usuario) y Controlador (gestión de la lógica de la aplicación).

## Funcionalidad

### Sección de Usuario

#### 1. **Página Principal**
   - **Vista de Productos**: Los usuarios pueden ver un listado de productos con su imagen, nombre, descripción breve y opciones para añadir al carrito o ver más detalles.
   - **Barra de Búsqueda**: Los usuarios pueden buscar productos por nombre, categoría o características.
   - **Filtrado por Categorías**: Los productos se organizan en categorías, facilitando la navegación.

   ![image](https://github.com/user-attachments/assets/0bbbb427-c57f-4e4b-890b-5e66dcd47cbd)
   ![image](https://github.com/user-attachments/assets/18a50995-55b8-461a-ae39-d76823adacc0)


#### 2. **Ficha de Producto**
   - Al hacer clic en "Ver detalles" de un producto, el usuario es redirigido a una página con información detallada sobre el producto, que incluye descripción completa, especificaciones técnicas, imágenes de alta calidad y precio.
   - Los usuarios pueden ver las opciones de cantidades y añadir productos al carrito directamente desde la página de detalles.
     
![Página Principal](https://github.com/user-attachments/assets/b961561c-2505-4c8c-b6be-13de10870b15)


#### 3. **Carrito de Compra**
   - **Añadir al Carrito**: Los usuarios pueden agregar productos al carrito de compra con facilidad.
   - **Visualización del Carrito**: El carrito de compras muestra los productos añadidos, la cantidad de cada uno, el precio total y un resumen de la compra.
   - **Edición del Carrito**: Los usuarios pueden actualizar las cantidades de productos o eliminar artículos del carrito.
   - **Checkout**: Una vez que los usuarios estén listos para comprar, pueden proceder al proceso de pago.

   ![Ficha de Producto](https://github.com/user-attachments/assets/ef9a4c00-91aa-4cc2-ac08-fa68773a812d)



   - **Persistencia del Carrito**: Si un usuario no ha iniciado sesión, los productos del carrito se guardan en la sesión del navegador, pero se pierden al cerrar la ventana del navegador.

#### 4. **Inicio de Sesión**
   - **Registro de Usuarios**: Los usuarios pueden registrarse proporcionando su nombre, correo electrónico y contraseña.
   - **Inicio de Sesión**: Los usuarios pueden iniciar sesión con su correo electrónico y contraseña para acceder a su perfil, ver el historial de pedidos y gestionar su carrito.


   ![Carrito de Compra](https://github.com/user-attachments/assets/7fbca5b4-b8c7-441b-b7e8-3b10f6b67f80)

#### 5. **Opciones del usuario**
   - **Visualización del Perfil**: Los usuarios pueden ver y editar sus datos personales (nombre, dirección de envío, etc.) desde su perfil(pero no esta implementado).
   - **Pedidos**: Los usuarios pueden ver un listado de sus pedidos anteriores, con detalles sobre cada pedido, incluyendo los productos comprados y el estado del envío.
   - **Seguimiento**: Los usuarios pueden ver el proceso de sus pedidos.

   ![image](https://github.com/user-attachments/assets/2b772e82-7913-459f-b333-8fade204d735)
   ![image](https://github.com/user-attachments/assets/f0035307-cd22-4e37-b7c0-f5d25728e58e)


#### 6. **Proceso de Compra**
   - **Métodos de Pago**: Durante el proceso de checkout, los usuarios pueden elegir entre diferentes métodos de pago (por ejemplo, tarjeta de crédito, PayPal).
   - **Confirmación de Pedido**: Una vez realizado el pago, los usuarios reciben una confirmación de pedido con los detalles de su compra.
   - **Notificación de Envío**: Los usuarios son notificados por correo electrónico cuando su pedido ha sido enviado.
     
   ![Captura de pantalla 2025-03-16 030105](https://github.com/user-attachments/assets/c1c88124-ae52-48be-a13e-1bffc73b7ad7)
   ![Captura de pantalla 2025-03-16 030537](https://github.com/user-attachments/assets/bc47ebd2-1c3b-4ddb-9378-40f4d56535b7)

### Sección de Administración

#### 1. **Panel de Control**
   - Acceso con credenciales de administrador.
   - Menú lateral con opciones: **Gestionar Productos**, **Gestionar Pedidos**, **Gestionar Usuarios** y con otras mas.
   - Los administradores pueden gestionar los productos disponibles en la tienda, ver los pedidos realizados y administrar los usuarios registrados.

   ![image](https://github.com/user-attachments/assets/326c3211-8670-44ba-97f3-0323f8866f7b)
   ![Panel de Control](https://github.com/user-attachments/assets/b14e129c-254c-4ebb-9f2d-51c37d40e8fc)

#### 2. **Gestión de Productos**
   - Los administradores pueden ver un listado completo de los productos disponibles en la tienda, con detalles como el ID del producto, nombre, categoría, precio, stock disponible y estado del producto.
   - Los administradores pueden añadir nuevos productos, especificando nombre, descripción, precio y otras características del producto.
   - También pueden editar los productos existentes y eliminar productos que ya no estén disponibles.

   ![Gestión de Productos](https://github.com/user-attachments/assets/6189b414-f60e-4fd9-8dfb-d21cc997ee8c)
   ![Gestión de Productos](https://github.com/user-attachments/assets/e2e9dba8-f49a-49f9-b0e7-7e7f85245041)
   ![Gestión de Productos](https://github.com/user-attachments/assets/797c12e9-8290-4a87-9ec7-27d2159fef2d)

#### 3. **Gestión de Pedidos**
   - Los administradores pueden ver todos los pedidos realizados por los usuarios, con detalles como el nombre del usuario, productos comprados, cantidades y estado del pedido.
   - Los administradores pueden cambiar el estado de los pedidos (por ejemplo, "Pendiente", "Enviado", "Entregado") y gestionar los pagos y envíos.

   ![image](https://github.com/user-attachments/assets/12404b47-e00f-4506-b04b-c5ea8ac7b45a)
   ![image](https://github.com/user-attachments/assets/101bf376-7dda-4ddb-b9ae-05af171ca4ca)



#### 4. **Gestión de Usuarios**
   - Los administradores pueden gestionar los usuarios registrados en la tienda.
   - Pueden crear, editar o eliminar usuarios, así como asignarles roles (por ejemplo, administrador o cliente).
   - La gestión de usuarios también permite visualizar el historial de compras de cada usuario.

   ![image](https://github.com/user-attachments/assets/66ac7623-4eed-4a3b-bf69-ce85a3052cbd)
   ![image](https://github.com/user-attachments/assets/3fcfd198-adc4-4190-8f81-fb97652c61fb)
   ![image](https://github.com/user-attachments/assets/bcae9ddf-6378-4b51-8a39-c3c2aac14eaa)


## Requisitos

- **PHP 7.4 o superior**: Requerido para ejecutar el backend de la tienda.
- **MySQL**: Para almacenar y gestionar los datos de la tienda, incluyendo los productos, usuarios y pedidos.
- **Servidor web**: Apache o Nginx para alojar la aplicación web.
- **Un entorno de desarrollo local como XAMPP o WAMP** (opcional, para pruebas locales).
- **Navegador web**: Para acceder a la tienda desde cualquier dispositivo conectado a Internet.

## Estructura del Proyecto

La estructura de archivos del proyecto es la siguiente:

