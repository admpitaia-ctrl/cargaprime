<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

/**
 * Guarda uma imagem de produto enviada pelo administrador.
 * Só aceita formatos de imagem reais e limita cada ficheiro a 5 MB.
 */
function save_product_image(array $file): ?string
{
    $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($error === UPLOAD_ERR_NO_FILE) return null;
    if ($error !== UPLOAD_ERR_OK) throw new RuntimeException('Não foi possível receber a imagem selecionada.');
    if ((int) ($file['size'] ?? 0) > 5 * 1024 * 1024) throw new RuntimeException('A imagem deve ter no máximo 5 MB.');

    $tmp = (string) ($file['tmp_name'] ?? '');
    $info = $tmp !== '' ? @getimagesize($tmp) : false;
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!$info || !isset($extensions[$info['mime']])) {
        throw new RuntimeException('Utilize uma imagem JPG, PNG ou WEBP válida.');
    }

    $relativeDirectory = 'assets/uploads/products';
    $absoluteDirectory = dirname(__DIR__) . '/' . $relativeDirectory;
    if (!is_dir($absoluteDirectory) && !mkdir($absoluteDirectory, 0775, true) && !is_dir($absoluteDirectory)) {
        throw new RuntimeException('Não foi possível criar a pasta das imagens.');
    }

    $filename = 'produto-' . bin2hex(random_bytes(8)) . '.' . $extensions[$info['mime']];
    if (!move_uploaded_file($tmp, $absoluteDirectory . '/' . $filename)) {
        throw new RuntimeException('Não foi possível guardar a imagem do produto.');
    }
    return $relativeDirectory . '/' . $filename;
}

function delete_uploaded_product_image(?string $path): void
{
    if (!$path || !str_starts_with($path, 'assets/uploads/products/')) return;
    $absolute = dirname(__DIR__) . '/' . $path;
    if (is_file($absolute)) @unlink($absolute);
}

function product_image(?string $path): string
{
    return $path ?: 'assets/images/products/gesso-ensacado.png';
}

/**
 * Converte uma quantidade enviada pelo formulário numa tonelagem segura.
 * A loja trabalha em intervalos de 0,5 t e nunca aceita zero, negativos,
 * valores infinitos ou texto disfarçado de número.
 */
function parse_tons(mixed $value): ?float
{
    if (!is_scalar($value)) return null;
    $normalized = str_replace(',', '.', trim((string) $value));
    if (!preg_match('/^(?:\d+)(?:\.\d+)?$/', $normalized)) return null;

    $quantity = (float) $normalized;
    if (!is_finite($quantity) || $quantity <= 0) return null;
    if (abs($quantity * 2 - round($quantity * 2)) > 0.000001) return null;
    return $quantity;
}

/**
 * Garante a estrutura mínima necessária para isolamento de carrinhos e
 * encaminhamento de pedidos de ajuda por produto/fábrica.
 * As operações são idempotentes e destinam-se ao ambiente académico XAMPP.
 */
function ensure_privacy_schema(): void
{
    static $checked = false;
    if ($checked) return;
    $checked = true;

    $pdo = db();
    $pdo->exec("CREATE TABLE IF NOT EXISTS cart_items (
        user_id INT UNSIGNED NOT NULL,
        product_id INT UNSIGNED NOT NULL,
        quantity_tons DECIMAL(10,2) NOT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (user_id, product_id),
        CONSTRAINT fk_cart_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        CONSTRAINT fk_cart_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");

    // MariaDB/XAMPP aceita ADD COLUMN IF NOT EXISTS; mantém bases antigas compatíveis.
    $pdo->exec("ALTER TABLE support_requests
        ADD COLUMN IF NOT EXISTS order_item_id INT UNSIGNED NULL AFTER order_id,
        ADD COLUMN IF NOT EXISTS factory_name VARCHAR(140) NULL AFTER order_item_id,
        ADD COLUMN IF NOT EXISTS factory_address VARCHAR(300) NULL AFTER factory_name");

    // O carrinho antigo era comum à sessão inteira. É descartado uma única vez
    // para impedir que dados de uma conta anterior apareçam noutra conta.
    if (isset($_SESSION['cart'])) {
        unset($_SESSION['cart']);
    }
}

/** Retorna o carrinho do utilizador autenticado ou o carrinho de convidado desta sessão. */
function cart_snapshot(): array
{
    ensure_privacy_schema();

    if (is_logged_in()) {
        $stmt = db()->prepare('SELECT product_id, quantity_tons FROM cart_items WHERE user_id = ? ORDER BY product_id');
        $stmt->execute([(int) $_SESSION['user']['id']]);
        $cart = [];
        foreach ($stmt as $row) {
            $cart[(int) $row['product_id']] = (float) $row['quantity_tons'];
        }
        return $cart;
    }

    $guest = $_SESSION['guest_cart'] ?? [];
    return is_array($guest) ? $guest : [];
}

function cart_set_item(int $productId, float $quantity): void
{
    ensure_privacy_schema();
    if (is_logged_in()) {
        $stmt = db()->prepare('INSERT INTO cart_items (user_id, product_id, quantity_tons) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE quantity_tons = VALUES(quantity_tons)');
        $stmt->execute([(int) $_SESSION['user']['id'], $productId, $quantity]);
        return;
    }
    $_SESSION['guest_cart'] ??= [];
    $_SESSION['guest_cart'][$productId] = $quantity;
}

function cart_remove_item(int $productId): void
{
    ensure_privacy_schema();
    if (is_logged_in()) {
        $stmt = db()->prepare('DELETE FROM cart_items WHERE user_id = ? AND product_id = ?');
        $stmt->execute([(int) $_SESSION['user']['id'], $productId]);
        return;
    }
    unset($_SESSION['guest_cart'][$productId]);
}

function cart_clear(): void
{
    ensure_privacy_schema();
    if (is_logged_in()) {
        $stmt = db()->prepare('DELETE FROM cart_items WHERE user_id = ?');
        $stmt->execute([(int) $_SESSION['user']['id']]);
        return;
    }
    unset($_SESSION['guest_cart']);
}
/**
 * Transfere o carrinho temporário do visitante para o utilizador
 * autenticado, preservando os itens que já existiam na conta.
 *
 * Cada quantidade é novamente comparada com o stock atual antes
 * de ser gravada no carrinho persistente.
 */
function merge_guest_cart_into_user_cart(): void
{
    ensure_privacy_schema();

    if (!is_logged_in()) {
        return;
    }

    $guestCart = $_SESSION['guest_cart'] ?? [];

    if (!is_array($guestCart) || !$guestCart) {
        unset($_SESSION['guest_cart']);
        return;
    }

    $productIds = array_values(
        array_unique(
            array_filter(
                array_map('intval', array_keys($guestCart)),
                static fn (int $id): bool => $id > 0
            )
        )
    );

    if (!$productIds) {
        unset($_SESSION['guest_cart']);
        return;
    }

    $placeholders = implode(
        ',',
        array_fill(0, count($productIds), '?')
    );

    $stmt = db()->prepare(
        "SELECT id, stock_tons, active
         FROM products
         WHERE id IN ($placeholders)"
    );

    $stmt->execute($productIds);

    $products = [];

    foreach ($stmt->fetchAll() as $product) {
        $products[(int) $product['id']] = $product;
    }

    $userId = (int) $_SESSION['user']['id'];

    foreach ($guestCart as $productId => $quantity) {
        $productId = (int) $productId;
        $quantity = parse_tons($quantity);

        if (
            $productId <= 0 ||
            $quantity === null ||
            !isset($products[$productId])
        ) {
            continue;
        }

        $product = $products[$productId];

        if ((int) $product['active'] !== 1) {
            continue;
        }

        $stock = (float) $product['stock_tons'];

        if ($stock < 0.5) {
            continue;
        }

        /*
         * Se o visitante tinha uma quantidade superior ao stock que
         * existe agora, preservamos apenas até ao limite disponível.
         * O checkout continuará a fazer a validação final.
         */
        $quantity = min($quantity, $stock);

        $existing = db()->prepare(
            'SELECT quantity_tons
             FROM cart_items
             WHERE user_id = ?
               AND product_id = ?
             LIMIT 1'
        );

        $existing->execute([
            $userId,
            $productId
        ]);

        $existingQuantity = $existing->fetchColumn();

        if ($existingQuantity !== false) {
            /*
             * Se o utilizador já tinha o mesmo produto no carrinho,
             * mantém-se a maior quantidade válida em vez de somar.
             *
             * Isto evita duplicar acidentalmente uma seleção feita
             * antes e depois da autenticação.
             */
            $quantity = max(
                $quantity,
                (float) $existingQuantity
            );

            $quantity = min($quantity, $stock);
        }

        $save = db()->prepare(
            'INSERT INTO cart_items
                (user_id, product_id, quantity_tons)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE
                quantity_tons = VALUES(quantity_tons)'
        );

        $save->execute([
            $userId,
            $productId,
            $quantity
        ]);
    }

    unset($_SESSION['guest_cart']);
}

function cart_count(): float
{
    return array_sum(cart_snapshot());
}

ensure_privacy_schema();

