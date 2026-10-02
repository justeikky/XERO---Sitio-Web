document.addEventListener('DOMContentLoaded', () => {
    const traducciones = {
        es: {
            'XERO - Productos': 'XERO - Productos', 'XERO - Categorías': 'XERO - Categorías', 'XERO - Proveedores': 'XERO - Proveedores', 'XERO - Tiendas': 'XERO - Tiendas', 'XERO - Informes de inventario': 'XERO - Informes de inventario', 'XERO - Configuración': 'XERO - Configuración', 'XERO - Añadir registro': 'XERO - Añadir registro', 'XERO - Editar producto': 'XERO - Editar producto', 'XERO - Editar categoría': 'XERO - Editar categoría', 'XERO - Editar proveedor': 'XERO - Editar proveedor', 'XERO - Editar tienda': 'XERO - Editar tienda', 'XERO - Registrar movimiento': 'XERO - Registrar movimiento',
            'Inicio': 'Inicio', 'Productos': 'Productos', 'Categorías': 'Categorías', 'Proveedores': 'Proveedores', 'Tiendas': 'Tiendas', 'Informes': 'Informes', 'Ajustes': 'Ajustes', 'Configuración': 'Configuración',
            'Añadir registro': 'Añadir registro', 'Cerrar Sesión': 'Cerrar Sesión', 'Buscar...': 'Buscar...', 'Buscar producto...': 'Buscar producto...', 'Buscar categoría...': 'Buscar categoría...', 'Buscar proveedor...': 'Buscar proveedor...', 'Buscar tienda...': 'Buscar tienda...', 'Buscar en informes...': 'Buscar en informes...', 'Buscar en ajustes...': 'Buscar en ajustes...', '¿Olvidaste tu contraseña?': '¿Olvidaste tu contraseña?', 'Iniciar Sesión': 'Iniciar Sesión', 'Registrarse': 'Registrarse', 'Regístrate': 'Regístrate', 'Inicia sesión': 'Inicia sesión',
            'Actividad reciente': 'Actividad reciente', 'Ventas': 'Ventas', 'Confirmado': 'Confirmado', 'Empaquetado': 'Empaquetado', 'Reembolsado': 'Reembolsado', 'Enviado': 'Enviado', 'Categorías de artículos': 'Categorías de artículos', 'VER MÁS': 'VER MÁS',
            'Configuración del Sistema': 'Configuración del Sistema', 'Perfil de Usuario': 'Perfil de Usuario', 'Cambiar imagen': 'Cambiar imagen', 'Nombre Completo': 'Nombre Completo', 'Correo Electrónico': 'Correo Electrónico', 'Rol de Usuario': 'Rol de Usuario', 'Administrador Principal': 'Administrador Principal', 'Teléfono': 'Teléfono', 'Guardar Cambios': 'Guardar Cambios', 'Preferencias Generales': 'Preferencias Generales', 'Idioma del Sistema': 'Idioma del Sistema', 'Español': 'Español', 'Inglés': 'Inglés', 'English': 'English', 'Moneda por Defecto': 'Moneda por Defecto', 'Notificaciones de Inventario': 'Notificaciones de Inventario', 'Alertas por Correo': 'Alertas por Correo', 'Guardar Preferencias': 'Guardar Preferencias',
            'Añadir producto': 'Añadir producto', 'Nuevo Producto': 'Nuevo Producto', 'Nueva Categoría': 'Nueva Categoría', 'Nuevo proveedor': 'Nuevo proveedor', 'Nueva tienda': 'Nueva tienda', 'Inventario de Productos': 'Inventario de Productos', 'Categorías de Productos': 'Categorías de Productos', 'Ubicaciones y Tiendas': 'Ubicaciones y Tiendas', 'Informes de inventario': 'Informes de inventario', 'Entradas y salidas': 'Entradas y salidas', 'Solo entradas': 'Solo entradas', 'Solo salidas': 'Solo salidas', 'Filtrar': 'Filtrar', 'Imprimir informe': 'Imprimir informe', 'Aún no hay productos': 'Aún no hay productos', 'Aún no hay categorías': 'Aún no hay categorías', 'Aún no hay proveedores': 'Aún no hay proveedores', 'Aún no hay tiendas': 'Aún no hay tiendas', 'Registrar entrada o salida': 'Registrar entrada o salida', 'Editar producto': 'Editar producto', 'Editar categoría': 'Editar categoría', 'Editar proveedor': 'Editar proveedor', 'Editar tienda': 'Editar tienda', 'Guardar cambios': 'Guardar cambios', 'Ver artículos': 'Ver artículos', 'Ver informe': 'Ver informe', 'Entrada': 'Entrada', 'Salida': 'Salida', 'Cantidad': 'Cantidad', 'Fecha': 'Fecha', 'Responsable': 'Responsable', 'Movimiento de inventario': 'Movimiento de inventario', 'Stock actual:': 'Stock actual:', 'Correo electrónico': 'Correo electrónico', 'Contraseña': 'Contraseña', 'Recordarme': 'Recordarme', 'Nombre completo': 'Nombre completo', 'Tu nombre': 'Tu nombre', 'Crea tu cuenta para comenzar': 'Crea tu cuenta para comenzar', 'Ingresa a tu cuenta de administración': 'Ingresa a tu cuenta de administración'
        },
        en: {
            'XERO - Productos': 'XERO - Products', 'XERO - Categorías': 'XERO - Categories', 'XERO - Proveedores': 'XERO - Suppliers', 'XERO - Tiendas': 'XERO - Stores', 'XERO - Informes de inventario': 'XERO - Inventory reports', 'XERO - Configuración': 'XERO - Settings', 'XERO - Añadir registro': 'XERO - Add record', 'XERO - Editar producto': 'XERO - Edit product', 'XERO - Editar categoría': 'XERO - Edit category', 'XERO - Editar proveedor': 'XERO - Edit supplier', 'XERO - Editar tienda': 'XERO - Edit store', 'XERO - Registrar movimiento': 'XERO - Record movement',
            'Inicio': 'Home', 'Productos': 'Products', 'Categorías': 'Categories', 'Proveedores': 'Suppliers', 'Tiendas': 'Stores', 'Informes': 'Reports', 'Ajustes': 'Settings', 'Configuración': 'Settings',
            'Añadir registro': 'Add record', 'Cerrar Sesión': 'Log out', 'Buscar...': 'Search...', 'Buscar producto...': 'Search products...', 'Buscar categoría...': 'Search categories...', 'Buscar proveedor...': 'Search suppliers...', 'Buscar tienda...': 'Search stores...', 'Buscar en informes...': 'Search reports...', 'Buscar en ajustes...': 'Search settings...', '¿Olvidaste tu contraseña?': 'Forgot your password?', 'Iniciar Sesión': 'Log in', 'Registrarse': 'Sign up', 'Regístrate': 'Sign up', 'Inicia sesión': 'Log in',
            'Actividad reciente': 'Recent activity', 'Ventas': 'Sales', 'Confirmado': 'Confirmed', 'Empaquetado': 'Packed', 'Reembolsado': 'Refunded', 'Enviado': 'Shipped', 'Categorías de artículos': 'Item categories', 'VER MÁS': 'SEE MORE',
            'Configuración del Sistema': 'System Settings', 'Perfil de Usuario': 'User Profile', 'Cambiar imagen': 'Change image', 'Nombre Completo': 'Full Name', 'Correo Electrónico': 'Email', 'Rol de Usuario': 'User Role', 'Administrador Principal': 'Main Administrator', 'Teléfono': 'Phone', 'Guardar Cambios': 'Save Changes', 'Preferencias Generales': 'General Preferences', 'Idioma del Sistema': 'System Language', 'Español': 'Spanish', 'Inglés': 'English', 'English': 'English', 'Moneda por Defecto': 'Default currency', 'Notificaciones de Inventario': 'Inventory notifications', 'Alertas por Correo': 'Email alerts', 'Guardar Preferencias': 'Save preferences',
            'Añadir producto': 'Add product', 'Nuevo Producto': 'New product', 'Nueva Categoría': 'New category', 'Nuevo proveedor': 'New supplier', 'Nueva tienda': 'New store', 'Inventario de Productos': 'Product inventory', 'Categorías de Productos': 'Product categories', 'Ubicaciones y Tiendas': 'Locations and stores', 'Informes de inventario': 'Inventory reports', 'Entradas y salidas': 'Entries and exits', 'Solo entradas': 'Entries only', 'Solo salidas': 'Exits only', 'Filtrar': 'Filter', 'Imprimir informe': 'Print report', 'Aún no hay productos': 'No products yet', 'Aún no hay categorías': 'No categories yet', 'Aún no hay proveedores': 'No suppliers yet', 'Aún no hay tiendas': 'No stores yet', 'Registrar entrada o salida': 'Record entry or exit', 'Editar producto': 'Edit product', 'Editar categoría': 'Edit category', 'Editar proveedor': 'Edit supplier', 'Editar tienda': 'Edit store', 'Guardar cambios': 'Save changes', 'Ver artículos': 'View items', 'Ver informe': 'View report', 'Entrada': 'Entry', 'Salida': 'Exit', 'Cantidad': 'Quantity', 'Fecha': 'Date', 'Responsable': 'Responsible person', 'Movimiento de inventario': 'Inventory movement', 'Stock actual:': 'Current stock:', 'Correo electrónico': 'Email', 'Contraseña': 'Password', 'Recordarme': 'Remember me', 'Nombre completo': 'Full name', 'Tu nombre': 'Your name', 'Crea tu cuenta para comenzar': 'Create your account to get started', 'Ingresa a tu cuenta de administración': 'Sign in to your administrator account'
        }
    };

    let idioma = localStorage.getItem('idioma') || 'es';

    function traducirPagina() {
        const diccionario = traducciones[idioma];
        const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
        const nodosTexto = [];
        let nodoActual;
        while ((nodoActual = walker.nextNode())) {
            nodosTexto.push(nodoActual);
        }
        nodosTexto.forEach((nodo) => {
            const textoOriginal = nodo.nodeValue.trim();
            if (diccionario[textoOriginal]) {
                nodo.nodeValue = nodo.nodeValue.replace(textoOriginal, diccionario[textoOriginal]);
            }
        });
        document.querySelectorAll('[placeholder]').forEach((elemento) => {
            if (diccionario[elemento.placeholder]) {
                elemento.placeholder = diccionario[elemento.placeholder];
            }
        });
        if (diccionario[document.title]) {
            document.title = diccionario[document.title];
        }
        document.documentElement.lang = idioma;
    }

    const selectorIdioma = document.getElementById('idioma-sistema');
    if (selectorIdioma) {
        selectorIdioma.value = idioma;
        selectorIdioma.addEventListener('change', () => {
            idioma = selectorIdioma.value === 'en' ? 'en' : 'es';
            localStorage.setItem('idioma', idioma);
            window.location.reload();
        });
    }

    window.cambiarIdioma = () => {
        idioma = idioma === 'es' ? 'en' : 'es';
        localStorage.setItem('idioma', idioma);
        window.location.reload();
    };

    const botonImprimir = document.getElementById('imprimir-informe');
    if (botonImprimir) {
        botonImprimir.addEventListener('click', () => window.print());
    }

    const botonMenu = document.getElementById('sidebar-toggle');
    if (botonMenu) {
        const claveMenuContraido = 'xero-menu-contraido';
        const panelMenu = document.querySelector('.sidebar');
        const actualizarMenu = (contraido) => {
            document.body.classList.toggle('sidebar-collapsed', contraido);
            botonMenu.setAttribute('aria-expanded', String(!contraido));
            botonMenu.setAttribute('aria-label', contraido ? 'Abrir navegación' : 'Cerrar navegación');
            botonMenu.title = contraido ? 'Abrir navegación' : 'Cerrar navegación';
            panelMenu.setAttribute('aria-hidden', String(contraido));
            panelMenu.inert = contraido;
        };

        actualizarMenu(localStorage.getItem(claveMenuContraido) === 'true');
        botonMenu.addEventListener('click', () => {
            const contraido = !document.body.classList.contains('sidebar-collapsed');
            localStorage.setItem(claveMenuContraido, String(contraido));
            actualizarMenu(contraido);
        });
    }

    const botonNotificaciones = document.getElementById('notification-button');
    const panelNotificaciones = document.getElementById('notification-panel');
    if (botonNotificaciones && panelNotificaciones) {
        botonNotificaciones.addEventListener('click', () => {
            const estaAbierto = !panelNotificaciones.hidden;
            panelNotificaciones.hidden = estaAbierto;
            botonNotificaciones.setAttribute('aria-expanded', String(!estaAbierto));
        });

        document.addEventListener('click', (evento) => {
            if (!evento.target.closest('.notification-wrapper')) {
                panelNotificaciones.hidden = true;
                botonNotificaciones.setAttribute('aria-expanded', 'false');
            }
        });
    }

    const botonHistorial = document.getElementById('history-button');
    const panelHistorial = document.getElementById('history-panel');
    if (botonHistorial && panelHistorial) {
        botonHistorial.addEventListener('click', () => {
            const estaAbierto = !panelHistorial.hidden;
            panelHistorial.hidden = estaAbierto;
            botonHistorial.setAttribute('aria-expanded', String(!estaAbierto));
        });

        document.addEventListener('click', (evento) => {
            if (!evento.target.closest('.history-wrapper')) {
                panelHistorial.hidden = true;
                botonHistorial.setAttribute('aria-expanded', 'false');
            }
        });
    }

    const selectorAvatar = document.getElementById('avatar-input');
    const vistaPreviaAvatar = document.getElementById('avatar-preview');
    if (selectorAvatar && vistaPreviaAvatar) {
        selectorAvatar.addEventListener('change', () => {
            const archivo = selectorAvatar.files[0];
            if (archivo && archivo.type.startsWith('image/')) {
                vistaPreviaAvatar.src = URL.createObjectURL(archivo);
            }
        });
    }

    const buscador = document.getElementById('busqueda-global');
    if (buscador) {
        const elementosFiltrables = document.querySelectorAll('.product-card, .category-card, .supplier-card, .store-card, .report-table tbody tr:not(.report-empty)');

        buscador.addEventListener('input', () => {
            const consulta = buscador.value.trim().toLocaleLowerCase();
            elementosFiltrables.forEach((elemento) => {
                const contenido = elemento.textContent.toLocaleLowerCase();
                elemento.hidden = consulta !== '' && !contenido.includes(consulta);
            });
        });
    }

    traducirPagina();
});
