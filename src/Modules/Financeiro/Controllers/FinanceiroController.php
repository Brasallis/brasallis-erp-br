<?php

namespace App\Modules\Financeiro\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Modules\Financeiro\Repositories\FinanceiroRepository;

/**
 * FinanceiroController — gerencia as requisições de finanças e fluxo de caixa.
 */
class FinanceiroController
{
    public function __construct(private FinanceiroRepository $repository) {}

    public function index(Request $request, Response $response): void
    {
        $resumo = $this->repository->getResumoCaixaToday();
        $movimentacoes = $this->repository->getMovimentacoes();
        
        $response->view('financeiro/index', compact('resumo', 'movimentacoes'));
    }
}
