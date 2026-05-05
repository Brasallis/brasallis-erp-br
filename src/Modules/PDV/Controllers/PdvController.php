<?php

namespace App\Modules\PDV\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Modules\PDV\Services\PdvService;
use App\Modules\Estoque\Repositories\ProdutoRepository;
use Exception;

/**
 * PdvController — gerencia as requisições do Frente de Caixa.
 */
class PdvController
{
    public function __construct(
        private PdvService $service,
        private ProdutoRepository $produtoRepo
    ) {}

    public function index(Request $request, Response $response): void
    {
        $response->view('pdv/index');
    }

    public function processSale(Request $request, Response $response): void
    {
        $data = $request->json();
        $items = $data['items'] ?? [];
        $payments = $data['payments'] ?? [];
        $clienteId = isset($data['cliente_id']) ? (int)$data['cliente_id'] : null;
        $discountAmount = isset($data['discount_amount']) ? (float)$data['discount_amount'] : 0.0;
        
        // Fallback p/ o modelo antigo se mandarem request legacy (1 método só)
        if (empty($payments) && !empty($data['payment_method'])) {
            $totalLegacy = 0;
            foreach ($items as $it) {
                $totalLegacy += ((float)($it['qty'] ?? 1)) * ((float)($it['price'] ?? 0));
            }
            $payments[] = [
                'method' => $data['payment_method'],
                'value'  => $totalLegacy
            ];
        }

        if (empty($items)) {
            $response->json(['success' => false, 'error' => 'Carrinho vazio.'], 400);
            return;
        }
        
        if (empty($payments)) {
            $response->json(['success' => false, 'error' => 'Forma de pagamento não informada.'], 400);
            return;
        }

        try {
            $vendaId = $this->service->finalizarVenda($items, $payments, $clienteId, $discountAmount);
            $response->json([
                'success' => true, 
                'venda_id' => $vendaId, 
                'message' => 'Venda finalizada com sucesso!'
            ]);
        } catch (Exception $e) {
            $response->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function searchProducts(Request $request, Response $response): void
    {
        $query = $request->input('q') ?? '';
        // Reaproveitando o ProdutoRepository
        $products = $this->produtoRepo->search($query);
        $response->json($products);
    }
}
