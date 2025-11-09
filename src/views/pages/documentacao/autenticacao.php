<div class="card mb-4" style="background: rgba(255,255,255,0.08); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1); border-radius: 1rem;">
    <div class="card-header text-white" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); border-radius: 1rem 1rem 0 0;">
        <h4 class="mb-0"><i class="fas fa-lock"></i> Autenticação</h4>
    </div>
    <div class="card-body">
        <p class="text-light mb-4">
            Todas as requisições à API devem incluir um token de autenticação no cabeçalho <code style="background: rgba(0,0,0,0.3); color: #87ceeb; padding: 0.25rem 0.5rem; border-radius: 0.25rem;">Authorization</code>.
        </p>

        <h5 class="text-light mb-3"><i class="fas fa-key text-warning"></i> Formato do Token</h5>
        <div style="background: #1e1e1e; border: 1px solid #404040; border-radius: 0.375rem; padding: 1rem; margin-bottom: 2rem;">
            <pre style="margin: 0;"><code style="color: #d4d4d4; font-family: 'Courier New', monospace;">Authorization: Bearer <span style="color: #ce9178;">SUA_CHAVE_API_AQUI</span></code></pre>
        </div>

        <div class="alert" style="background: rgba(255, 193, 7, 0.15); border-left: 4px solid #ffc107; color: #ffc107; border-radius: 0.5rem;">
            <i class="fas fa-exclamation-triangle"></i> <strong>Atenção:</strong>
            Mantenha sua chave de API segura. Nunca a exponha em código frontend público ou repositórios Git.
        </div>
    </div>
</div>