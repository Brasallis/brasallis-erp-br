<?php
// developers.php
require_once __DIR__ . '/includes/funcoes.php';

// Context-Aware Header
$is_logged_in = isset($_SESSION['user_id']);

if ($is_logged_in) {
    include_once __DIR__ . '/includes/cabecalho.php';
} else {
    include_once __DIR__ . '/includes/header.php';
    echo '<style>body { background-color: #f8f9fa; }</style>';
    echo '<div class="container pt-5 mt-5">'; // Spacing for public fixed navbar
}
?>

<style>
    /* Custom Styles for Docs inside the Admin Panel */
    .docs-container {
        max-width: 1200px;
        margin: 0 auto;
    }
    .code-block {
        background-color: #f8f9fa;
        border-radius: 6px;
        padding: 1rem;
        border: 1px solid #e9ecef;
        font-family: 'Fira Code', monospace;
        font-size: 0.85rem;
        color: #2c3e50;
        overflow-x: auto;
    }
    .method-badge {
        font-size: 0.75rem;
        font-weight: bold;
        padding: 4px 8px;
        border-radius: 4px;
        text-transform: uppercase;
    }
    .method-get { background-color: #e3f2fd; color: #0d47a1; }
    .method-post { background-color: #e8f5e9; color: #1b5e20; }
    .endpoint-row {
        border-left: 4px solid #dee2e6;
        margin-bottom: 2rem;
        padding-left: 1.5rem;
    }
    .nav-pills .nav-link {
        color: #555;
        border-radius: 8px;
        padding: 10px 15px;
        margin-bottom: 5px;
        transition: all 0.2s;
    }
    .nav-pills .nav-link:hover {
        background-color: rgba(0,0,0,0.03);
        color: #000;
    }
    .nav-pills .nav-link.active {
        background-color: #0d6efd;
        color: white;
        font-weight: 600;
        box-shadow: 0 2px 5px rgba(13, 110, 253, 0.3);
    }

    /* Missing Dashboard Styles for Public View */
    .card-dashboard {
        border: none;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.05) !important;
        transition: transform 0.2s, box-shadow 0.2s;
        background: white;
    }
    .card-dashboard:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.1) !important;
    }
    .text-navy { color: #0A2647; }
    .bg-light { background-color: #f8f9fa !important; }
    
    /* Ensure icons are visible */
    .fas, .fab { display: inline-block; }
</style>

<div class="docs-container pb-5">
    
    <!-- Header Page -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1">Developer API</h2>
            <p class="text-muted mb-0">Documentação oficial para integração e desenvolvimento.</p>
        </div>
        <div>
            <?php if(isset($_SESSION['user_id'])): ?>
                <a href="admin/painel_admin.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-2"></i>Voltar</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="row g-4">
        <!-- Sidebar Navigation -->
        <div class="col-lg-3">
            <div class="card card-dashboard border-0 shadow-sm sticky-top" style="top: 100px; z-index: 1000;">
                <div class="card-body p-3">
                    <h6 class="fw-bold text-uppercase text-secondary small mb-3 px-2">Tópicos</h6>
                    <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                        <a class="nav-link active" id="v-pills-intro-tab" data-bs-toggle="pill" href="#v-pills-intro" role="tab" aria-selected="true"><i class="fas fa-book-open me-2"></i>Visão Geral</a>
                        <a class="nav-link" id="v-pills-auth-tab" data-bs-toggle="pill" href="#v-pills-auth" role="tab" aria-selected="false"><i class="fas fa-key me-2"></i>Autenticação</a>
                        <a class="nav-link" id="v-pills-clientes-tab" data-bs-toggle="pill" href="#v-pills-clientes" role="tab" aria-selected="false"><i class="fas fa-user-friends me-2"></i>Endpoints: Clientes</a>
                        <a class="nav-link" id="v-pills-errors-tab" data-bs-toggle="pill" href="#v-pills-errors" role="tab" aria-selected="false"><i class="fas fa-exclamation-triangle me-2"></i>Erros Comuns</a>
                    </div>
                    
                    <hr class="my-3 opacity-25">
                    
                    <div class="px-2">
                        <small class="text-muted d-block mb-1">Versão API</small>
                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">v1.0.0 (Stable)</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-lg-9">
            <div class="tab-content" id="v-pills-tabContent">
                
                <!-- Introduction -->
                <div class="tab-pane fade show active" id="v-pills-intro" role="tabpanel">
                    <div class="card card-dashboard border-0 shadow-sm p-4 mb-4">
                        <h3 class="fw-bold text-primary mb-4">Introdução</h3>
                        <p class="lead text-dark">A API do Gerenciador de Estoque permite conectar sistemas externos para leitura e escrita de dados de forma segura.</p>
                        <p>Utilizamos o padrão RESTful com respostas em JSON. Todas as comunicações devem ser feitas via HTTPS para garantir a segurança dos dados.</p>
                        
                        <div class="alert alert-light border d-flex align-items-center mt-4">
                            <i class="fas fa-server fa-2x text-primary me-3"></i>
                            <div>
                                <strong>Base URL Principal</strong>
                                <div class="font-monospace text-muted mt-1 select-all">http://seu-dominio.com/api/v1</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Authentication -->
                <div class="tab-pane fade" id="v-pills-auth" role="tabpanel">
                    <div class="card card-dashboard border-0 shadow-sm p-4 mb-4">
                        <h3 class="fw-bold text-dark mb-4">Autenticação</h3>
                        <p>A autenticação é baseada em <strong>Tokens de Acesso (Bearer Token)</strong>. Você deve incluir seu token no cabeçalho de todas as requisições.</p>
                        
                        <h5 class="mt-4 mb-3 fs-6 fw-bold">Header HTTP</h5>
                        <div class="code-block">Authorization: Bearer sk_Production_123456...</div>
                        
                        <div class="alert alert-warning mt-4 border-0 d-flex gap-3">
                            <i class="fas fa-lock mt-1"></i>
                            <div>
                                <strong>Segurança da Chave</strong>
                                <br>Nunca exponha sua API Key em scripts públicos (frontend JS, apps mobile descomplicados). As chaves devem ser mantidas no servidor.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CRM: Clientes -->
                <div class="tab-pane fade" id="v-pills-clientes" role="tabpanel">
                    <div class="card card-dashboard border-0 shadow-sm p-4 mb-4">
                        <div class="d-flex align-items-center mb-4">
                            <div class="bg-primary text-white rounded p-2 me-3"><i class="fas fa-users fa-lg"></i></div>
                            <h3 class="fw-bold text-dark mb-0">Recurso: Clientes</h3>
                        </div>
                        
                        <p class="mb-5 text-muted">Gerencie a base de clientes do CRM (Pessoas Físicas e Jurídicas).</p>

                        <!-- GET -->
                        <div class="endpoint-row">
                            <div class="d-flex align-items-center mb-2">
                                <span class="method-badge method-get me-2">GET</span>
                                <code class="fs-5 text-dark">/crm/clientes.php</code>
                            </div>
                            <p>Lista clientes com paginação e busca.</p>
                            
                            <div class="table-responsive mt-3">
                                <table class="table table-sm table-borderless bg-light rounded">
                                    <thead><tr class="text-uppercase small text-muted"><th>Parâmetro</th><th>Tipo</th><th>Descrição</th></tr></thead>
                                    <tbody>
                                        <tr><td class="font-monospace">page</td><td>int</td><td>Número da página (Default: 1)</td></tr>
                                        <tr><td class="font-monospace">limit</td><td>int</td><td>Itens por página (Default: 50)</td></tr>
                                        <tr><td class="font-monospace">search</td><td>string</td><td>Termo de busca (Nome, Email, CPF)</td></tr>
                                    </tbody>
                                </table>
                            </div>

                            <h6 class="fw-bold small text-uppercase mt-4 text-secondary">Exemplo (cURL)</h6>
                            <div class="code-block">curl -X GET "http://localhost/api/v1/crm/clientes.php?page=1" \
  -H "Authorization: Bearer sk_test_..."</div>
                        </div>

                        <hr class="my-5">

                        <!-- POST -->
                        <div class="endpoint-row mb-0">
                            <div class="d-flex align-items-center mb-2">
                                <span class="method-badge method-post me-2">POST</span>
                                <code class="fs-5 text-dark">/crm/clientes.php</code>
                            </div>
                            <p>Cadastra um novo cliente no banco de dados.</p>
                            
                            <h6 class="fw-bold small text-uppercase mt-3 text-secondary">Body (JSON)</h6>
                             <div class="code-block">{
  "nome": "João da Silva",
  "email": "joao@email.com",
  "tipo": "PF" // ou "PJ"
}</div>
                        </div>
                    </div>
                </div>

                <!-- Errors -->
                <div class="tab-pane fade" id="v-pills-errors" role="tabpanel">
                    <div class="card card-dashboard border-0 shadow-sm p-4 mb-4">
                        <h3 class="fw-bold text-dark mb-4">Glossário de Erros</h3>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="bg-light">
                                    <tr><th style="width: 100px;">Código</th><th>Significado</th></tr>
                                </thead>
                                <tbody>
                                    <tr><td><span class="badge bg-success">200</span></td><td>Requisição processada com sucesso.</td></tr>
                                    <tr><td><span class="badge bg-success">201</span></td><td>Recurso criado (Create).</td></tr>
                                    <tr><td><span class="badge bg-warning text-dark">400</span></td><td>Erro na requisição (JSON inválido ou campos faltando).</td></tr>
                                    <tr><td><span class="badge bg-danger">401</span></td><td>Não autorizado (Token inválido ou expirado).</td></tr>
                                    <tr><td><span class="badge bg-danger">403</span></td><td>Proibido (Sem permissão de acesso).</td></tr>
                                    <tr><td><span class="badge bg-secondary">404</span></td><td>Endpoint ou Recurso não encontrado.</td></tr>
                                    <tr><td><span class="badge bg-secondary">500</span></td><td>Erro interno do servidor.</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php 
if ($is_logged_in) {
    include_once __DIR__ . '/includes/rodape.php';
} else {
    echo '</div>'; // Close container
    echo '<footer class="bg-white border-top py-4 mt-5">
            <div class="container text-center text-muted small">
                <p class="mb-0">&copy; ' . date('Y') . ' Brasallis. API Documentation.</p>
                <a href="index.php" class="text-decoration-none mt-2 d-inline-block">Voltar para Home</a>
            </div>
          </footer>';
    echo '</body></html>';
}
?>
