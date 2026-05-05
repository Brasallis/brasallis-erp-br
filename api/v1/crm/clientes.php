<?php
// api/v1/crm/clientes.php
require_once __DIR__ . '/../config.php';

$conn = get_db_connection();
$auth_data = authenticate_api_request($conn);
$empresa_id = $auth_data['empresa_id'];

$method = $_SERVER['REQUEST_METHOD'];

// Parse JSON Body
$input = json_decode(file_get_contents("php://input"), true);

switch ($method) {
    case 'GET':
        // Listagem com Paginação (Server-Side Pagination)
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $offset = ($page - 1) * $limit;
        $search = isset($_GET['search']) ? $_GET['search'] : '';

        // Validação de limites
        if ($limit > 100) $limit = 100;

        $where_sql = "WHERE empresa_id = :empresa_id AND status = 'ativo'";
        $params = [':empresa_id' => $empresa_id];

        if (!empty($search)) {
            $where_sql .= " AND (nome LIKE :search OR email LIKE :search OR cpf_cnpj LIKE :search)";
            $params[':search'] = "%$search%";
        }

        // Count Total
        $count_stmt = $conn->prepare("SELECT COUNT(*) FROM clientes $where_sql");
        $count_stmt->execute($params);
        $total_records = $count_stmt->fetchColumn();

        // Fetch Data
        // Use Integer Bind for Limit/Offset in PDO (Needs specific type)
        $sql = "SELECT id, nome, tipo, cpf_cnpj, email, telefone, cidade, estado FROM clientes $where_sql ORDER BY nome LIMIT :limit OFFSET :offset";
        $stmt = $conn->prepare($sql);
        
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        send_json_response([
            'data' => $clientes,
            'meta' => [
                'total' => $total_records,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => ceil($total_records / $limit)
            ]
        ]);
        break;

    case 'POST':
        // Criação de Cliente
        if (empty($input['nome'])) {
            send_json_response(['error' => 'Nome is required'], 400);
        }

        $nome = $input['nome'];
        $tipo = $input['tipo'] ?? 'PF';
        $cpf_cnpj = $input['cpf_cnpj'] ?? null;
        $email = $input['email'] ?? null;
        $telefone = $input['telefone'] ?? null;
        $endereco = $input['endereco'] ?? null;
        $cidade = $input['cidade'] ?? null;
        $estado = $input['estado'] ?? null;

        try {
            $stmt = $conn->prepare("INSERT INTO clientes (empresa_id, nome, tipo, cpf_cnpj, email, telefone, endereco, cidade, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$empresa_id, $nome, $tipo, $cpf_cnpj, $email, $telefone, $endereco, $cidade, $estado]);
            $new_id = $conn->lastInsertId();

            send_json_response(['message' => 'Client created', 'id' => $new_id], 201);
        } catch (PDOException $e) {
            send_json_response(['error' => 'Failed to create client: ' . $e->getMessage()], 500);
        }
        break;

    default:
        send_json_response(['error' => 'Method not allowed'], 405);
        break;
}
?>
