-- Ejecuta este script una sola vez sobre la base de datos xero.
-- Los registros existentes se asignan a la primera cuenta cuyo nombre contenga
-- "juli" (Julián/Julian); si no existe, se asignan a la cuenta más antigua.
-- Verifica que @id_usuario_datos_existentes corresponda al propietario legítimo
-- de los datos actuales antes de ejecutar este script.
SET @id_usuario_datos_existentes = (
    SELECT id_usuario
    FROM usuario
    WHERE LOWER(nombre) LIKE '%juli%'
    ORDER BY id_usuario
    LIMIT 1
);
SET @id_usuario_datos_existentes = COALESCE(
    @id_usuario_datos_existentes,
    (SELECT MIN(id_usuario) FROM usuario)
);

ALTER TABLE categorias ADD COLUMN id_usuario INT NULL;
ALTER TABLE proveedores ADD COLUMN id_usuario INT NULL;
ALTER TABLE tiendas ADD COLUMN id_usuario INT NULL;
ALTER TABLE productos ADD COLUMN id_usuario INT NULL;

UPDATE categorias SET id_usuario = @id_usuario_datos_existentes WHERE id_usuario IS NULL;
UPDATE proveedores SET id_usuario = @id_usuario_datos_existentes WHERE id_usuario IS NULL;
UPDATE tiendas SET id_usuario = @id_usuario_datos_existentes WHERE id_usuario IS NULL;
UPDATE productos SET id_usuario = @id_usuario_datos_existentes WHERE id_usuario IS NULL;
UPDATE actividad SET id_usuario = @id_usuario_datos_existentes WHERE id_usuario = 1;

ALTER TABLE categorias
    MODIFY id_usuario INT NOT NULL,
    ADD INDEX idx_categorias_usuario (id_usuario);
ALTER TABLE proveedores
    MODIFY id_usuario INT NOT NULL,
    ADD INDEX idx_proveedores_usuario (id_usuario);
ALTER TABLE tiendas
    MODIFY id_usuario INT NOT NULL,
    ADD INDEX idx_tiendas_usuario (id_usuario);
ALTER TABLE productos
    MODIFY id_usuario INT NOT NULL,
    ADD INDEX idx_productos_usuario (id_usuario);
