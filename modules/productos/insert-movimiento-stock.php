<?php
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    require '../../global/connection.php';
    session_start();

    if (!isset($_SESSION['loggedInUser']['USERID'])) {
        echo "ERROR: Sesión expirada. Por favor, inicie sesión nuevamente.";
        exit;
    }

    try {
        $tipo = $_POST['mov_tipo'] ?? '';
        $prod = $_POST['mov_prod_code'] ?? '';
        $guia_orden = trim($_POST['mov_guia_orden'] ?? '');
        $prov = $_POST['mov_prov'] ?? null;
        $fec_venc = $_POST['mov_fec_venc'] ?? null;
        $obs = trim($_POST['mov_obs'] ?? '');
        $cant = floatval($_POST['mov_cantidad'] ?? 0);
        $user_id = $_SESSION['loggedInUser']['USERID'];

        if (empty($prod)) {
            echo "ERROR: Producto no seleccionado.";
            exit;
        }

        if ($cant == 0) {
            echo "ERROR: La cantidad debe ser distinta de cero.";
            exit;
        }

        $mov_type = ($tipo == "Ingreso") ? 1 : (($tipo == "Ajuste") ? 2 : 0);
        if ($mov_type == 0) {
            echo "ERROR: Tipo de movimiento no válido.";
            exit;
        }

        $pdo->beginTransaction();

        // 1. Insertar Movimiento
        if ($mov_type == 1) {
            $sql = "INSERT INTO tbl_warehouse_movement(type,product_id,quantity,observation,provider_id,doc_reference,expiration_date,user_id) VALUES(?,?,?,?,?,?,?,?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$mov_type, $prod, $cant, $obs, $prov, $guia_orden, $fec_venc, $user_id]);
        } else {
            $sql = "INSERT INTO tbl_warehouse_movement(type,product_id,quantity,observation,user_id) VALUES(?,?,?,?,?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$mov_type, $prod, $cant, $obs, $user_id]);
        }

        // 2. Actualizar Stock del Producto
        $stmtUpdate = $pdo->prepare("UPDATE tbl_product SET stock_quantity = stock_quantity + ? WHERE id = ?");
        $stmtUpdate->execute([$cant, $prod]);

        if ($stmtUpdate->rowCount() == 0) {
            $pdo->rollBack();
            echo "ERROR: No se pudo actualizar el stock del producto. Verifique que el producto exista.";
            exit;
        }

        $pdo->commit();
        echo "OK_INSERT";

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo "ERROR: " . $e->getMessage();
    }
} else {
    echo "ERROR: Acceso directo no permitido.";
}
