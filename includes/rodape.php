<?php if (isset($_SESSION['user_id'])) : ?>
  <footer class="mt-auto py-3 border-top bg-light">
      <div class="container d-flex flex-wrap justify-content-between align-items-center">
          <p class="col-md-4 mb-0 text-muted small">&copy; <?= date('Y') ?> Brasallis ERP</p>

          <ul class="nav col-md-4 justify-content-end list-unstyled d-flex small">
              <li class="ms-3"><a class="text-muted text-decoration-none" href="/developers.php"><i class="fas fa-code me-1"></i>Developers API</a></li>
              <li class="ms-3"><a class="text-muted text-decoration-none" href="#">Ajuda</a></li>
              <li class="ms-3"><a class="text-muted text-decoration-none" href="#">Sobre</a></li>
          </ul>
      </div>
  </footer>
  </div>
</main>
<?php endif; ?>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php if (isset($use_charts) && $use_charts): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<?php endif; ?>
<script src="/assets/js/admin.js"></script>
<script src="/assets/js/main.js"></script>
<?php if (isset($use_marked) && $use_marked): ?>
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<?php else: ?>
<!-- marked.js carregado somente nas páginas com chat AI -->
<?php endif; ?>
<script>
// A lógica de busca e navegação mobile foi centralizada no cabecalho.php para performance e consistência.
</script>




<?php if (isset($_SESSION['user_id'])) : ?>
<!-- AI Agent Offcanvas Interface -->
<div class="offcanvas offcanvas-end shadow-lg border-0" data-bs-scroll="true" tabindex="-1" id="offcanvasAIAgent" aria-labelledby="offcanvasAIAgentLabel" style="width: 420px; border-radius: 24px 0 0 24px;">
    <div class="offcanvas-header bg-white border-bottom border-light py-4 px-4">
        <div class="d-flex align-items-center gap-3">
            <div class="icon-circle bg-primary bg-opacity-10 text-primary shadow-sm" style="width: 42px; height: 42px; font-size: 1.2rem;">
                <i class="fas fa-sparkles"></i>
            </div>
            <div>
                <h5 class="offcanvas-title fw-bold text-navy mb-0" id="offcanvasAIAgentLabel">Brasallis AI</h5>
                <small class="text-muted fw-medium">Assistente Executivo</small>
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    
    <!-- Agent Selector -->
    <div class="px-4 py-3 bg-light border-bottom border-light d-flex flex-column gap-2">
        <select id="agent-selector" class="form-select rounded-pill border-0 shadow-sm fw-bold text-navy" style="font-size: 0.85rem;">
            <option value="">Selecione um Agente...</option>
            <!-- Populated via JS -->
        </select>
        <select id="model-selector" class="form-select rounded-pill border-0 shadow-sm fw-bold text-primary" style="font-size: 0.8rem; background-color: rgba(37, 99, 235, 0.05);">
            <optgroup label="Google Gemini">
                <option value="gemini-2.5-flash" selected>Gemini 2.5 Flash (Fast)</option>
                <option value="gemini-2.5-pro">Gemini 2.5 Pro (Deep Think)</option>
                <option value="gemini-2.0-flash">Gemini 2.0 Flash</option>
            </optgroup>
            <optgroup label="OpenAI">
                <option value="gpt-3.5-turbo">GPT-3.5 Turbo</option>
                <option value="gpt-4o">GPT-4o (Advanced)</option>
            </optgroup>
        </select>
    </div>

    <div class="offcanvas-body p-0 d-flex flex-column bg-light" style="height: 100%;">
        <!-- Chat Area -->
        <div id="chat-messages" class="flex-grow-1 p-4" style="overflow-y: auto; background: #f8fafc;">
            <div class="text-center text-muted mt-5">
                <div class="icon-circle bg-white shadow-sm mx-auto mb-3 text-secondary opacity-50" style="width: 64px; height: 64px; font-size: 2rem;">
                    <i class="fas fa-robot"></i>
                </div>
                <p class="small fw-medium">Selecione um agente e inicie a sessão.</p>
            </div>
        </div>

        <!-- Input Area -->
        <div class="p-3 bg-white border-top border-light">
            <div class="input-group bg-light rounded-4 border-0 p-1 shadow-sm" style="transition: all 0.3s ease;" id="chat-input-wrapper">
                <input type="text" id="chat-input" class="form-control border-0 bg-transparent ps-3 py-2" placeholder="Digite sua mensagem..." aria-label="Mensagem" style="font-size: 0.95rem; box-shadow: none;">
                <button class="btn btn-primary rounded-4 px-4 shadow-sm" type="button" id="btn-send-chat">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
            <div id="typing-indicator" class="text-primary small fw-bold ms-3 mt-2" style="display: none;">
                <span class="spinner-grow spinner-grow-sm me-1" role="status" aria-hidden="true"></span>
                Processando...
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const fab = document.getElementById('ai-agent-fab');
    // Ensure Bootstrap is loaded
    if (typeof bootstrap === 'undefined') {
        console.error('Bootstrap JS is not loaded or failed to load.');
        return;
    }
    
    // Check if elements exist before using them
    const offcanvasEl = document.getElementById('offcanvasAIAgent');
    if (!offcanvasEl || !fab) return;

    const bsOffcanvas = new bootstrap.Offcanvas(offcanvasEl);
    const chatInput = document.getElementById('chat-input');
    const sendBtn = document.getElementById('btn-send-chat');
    const messagesContainer = document.getElementById('chat-messages');
    const agentSelector = document.getElementById('agent-selector');
    const typingIndicator = document.getElementById('typing-indicator');

    if (!chatInput || !sendBtn || !messagesContainer || !agentSelector) return;

    let history = [];

    // Toggle Chat
    fab.addEventListener('click', () => bsOffcanvas.toggle());

    // Load Agents
    function loadAgents() {
        fetch('/api/get_agents_list.php')
            .then(r => r.json())
            .then(data => {
                if(data.length > 0) {
                    agentSelector.innerHTML = data.map(a => `<option value="${a.id}">${a.name} (${a.role})</option>`).join('');
                    // Select first active agent if none selected
                    if (!agentSelector.value) {
                         const active = data.find(a => a.status === 'active');
                         if(active) agentSelector.value = active.id;
                    }
                } else {
                    agentSelector.innerHTML = '<option>Sem agentes criados</option>';
                }
            })
            .catch(e => console.error("Erro ao carregar agentes", e));
    }
    loadAgents();

    // Expose Global Function to Open Chat with specific Agent
    window.openAgentChat = function(agentId) {
        bsOffcanvas.show();
        loadAgents(); // Reload to ensure list is fresh
        // Wait small delay for options to populate if needed, or set directly
        setTimeout(() => {
            if(agentId) agentSelector.value = agentId;
        }, 500);
    };

    // Send Message
    async function sendMessage() {
        const text = chatInput.value.trim();
        const agentId = agentSelector.value;
        const modelId = document.getElementById('model-selector').value;
        
        if (!text || !agentId) return;

        // UI User Message
        appendMessage('user', text);
        chatInput.value = '';
        typingIndicator.style.display = 'block';

        try {
            const res = await fetch('/api/chat_agent.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    agent_id: agentId,
                    message: text,
                    model: modelId,
                    history: history
                })
            });
            
            const data = await res.json();
            
            typingIndicator.style.display = 'none';
            
            if (data.error) {
                appendMessage('system', 'Erro: ' + data.error);
            } else {
                // Split response by delimiter and show bubbles sequentially
                const parts = data.response.split('<<<SPLIT>>>');
                
                // Function to show parts with delay
                const showParts = async () => {
                    for (const part of parts) {
                        if(part.trim()) {
                            appendMessage('model', part.trim());
                            await new Promise(r => setTimeout(r, 800)); // Small delay for natural feel
                        }
                    }
                    if (data.widget) {
                        renderWidget(data.widget);
                    }
                };
                showParts();

                history.push({role: 'user', content: text});
                history.push({role: 'model', content: data.response.replace(/<<<SPLIT>>>/g, '\n\n')}); // Store clean history
            }

        } catch (err) {
            typingIndicator.style.display = 'none';
            appendMessage('system', 'Erro de conexão.');
        }
    }

    sendBtn.addEventListener('click', sendMessage);
    chatInput.addEventListener('keypress', (e) => { if(e.key === 'Enter') sendMessage(); });

    function appendMessage(role, text) {
        const div = document.createElement('div');
        div.className = `d-flex mb-4 ${role === 'user' ? 'justify-content-end' : 'justify-content-start'}`;
        
        const bubble = document.createElement('div');
        bubble.className = `p-3 shadow-sm ${role === 'user' ? 'bg-primary text-white rounded-4 rounded-bottom-right-0' : 'bg-white text-dark border-light rounded-4 rounded-bottom-left-0'}`;
        bubble.style.maxWidth = '85%';
        bubble.style.fontSize = '0.9rem';
        bubble.style.lineHeight = '1.5';
        
        // Render Markdown if available, else text
        if (typeof marked !== 'undefined' && role !== 'user') {
             bubble.innerHTML = marked.parse(text);
        } else {
             bubble.innerHTML = text.replace(/\n/g, '<br>');
        }
        
        div.appendChild(bubble);
        messagesContainer.appendChild(div);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    function renderWidget(widget) {
        const div = document.createElement('div');
        div.className = 'mb-4 card border-0 shadow-sm rounded-4 overflow-hidden';
        
        let content = `<div class="card-header bg-navy text-white fw-bold small text-uppercase py-2"><i class="fas fa-table me-2"></i>${widget.title || 'Dados Estruturados'}</div>`;
        content += `<div class="card-body p-0">`;

        if (widget.type === 'table') {
            content += `<div class="table-responsive"><table class="table table-sm table-hover align-middle mb-0 small text-secondary"><thead><tr class="bg-light">`;
            if (widget.data.length > 0) {
                Object.keys(widget.data[0]).forEach(k => content += `<th class="text-uppercase text-muted" style="font-size:0.7rem;">${k}</th>`);
                content += `</tr></thead><tbody>`;
                widget.data.forEach(row => {
                    content += `<tr>`;
                    Object.values(row).forEach(v => content += `<td class="fw-medium">${v}</td>`);
                    content += `</tr>`;
                });
                content += `</tbody></table></div>`;
            }
        } else if (widget.type === 'card' || widget.type === 'list') {
            if (Array.isArray(widget.data)) {
                 content += '<ul class="list-group list-group-flush">';
                 widget.data.forEach(item => {
                     const txt = item.name ? item.name : JSON.stringify(item);
                     content += `<li class="list-group-item small fw-medium text-secondary py-3">${txt}</li>`;
                 });
                 content += '</ul>';
            } else {
                // Single object summary
                content += `<div class="p-4 text-secondary fw-medium small">${widget.text || JSON.stringify(widget.data)}</div>`;
            }
        }

        content += `</div>`;
        div.innerHTML = content;
        messagesContainer.appendChild(div);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
});
</script>
<?php endif; ?>
</body>
</html>
