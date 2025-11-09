<div class="card mb-4" style="background: rgba(255,255,255,0.08); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1); border-radius: 1rem;">
    <div class="card-header text-white" style="background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); border-radius: 1rem 1rem 0 0;">
        <h4 class="mb-0"><i class="fas fa-list"></i> Listar E-mails</h4>
    </div>
    <div class="card-body">
        <h5 class="text-light mb-3"><i class="fas fa-terminal text-success"></i> Endpoint</h5>
        <div style="background: rgba(0,0,0,0.3); padding: 1rem; border-radius: 0.5rem; margin-bottom: 2rem; border-left: 4px solid #a8edea;">
            <code style="color: #a6e22e; font-weight: bold;">GET</code>
            <code style="color: #87ceeb; font-size: 1.1rem; margin-left: 1rem;">/listarEmails</code>
        </div>

        <p class="text-light-50 mb-4">
            Retorna o histórico de todos os e-mails enviados pelo sistema, incluindo status, data, destinatário e assunto.
        </p>

        <h5 class="text-light mb-3"><i class="fas fa-code text-primary"></i> Exemplo de Resposta</h5>
        <div style="background: #1e1e1e; border: 1px solid #404040; border-radius: 0.375rem; padding: 1rem; overflow-x: auto;">
            <pre style="margin: 0;"><code style="color: #d4d4d4; font-family: 'Courier New', monospace; font-size: 0.9rem;">{
  <span style="color: #9cdcfe;">"result"</span>: [
    {
      <span style="color: #9cdcfe;">"idemail"</span>: <span style="color: #b5cea8;">1</span>,
      <span style="color: #9cdcfe;">"destinatario"</span>: <span style="color: #ce9178;">"cliente@example.com"</span>,
      <span style="color: #9cdcfe;">"assunto"</span>: <span style="color: #ce9178;">"Bem-vindo ao Sistema"</span>,
      <span style="color: #9cdcfe;">"status"</span>: <span style="color: #ce9178;">"enviado"</span>,
      <span style="color: #9cdcfe;">"data_envio"</span>: <span style="color: #ce9178;">"2025-11-09 14:35:20"</span>,
      <span style="color: #9cdcfe;">"idsistema"</span>: <span style="color: #b5cea8;">1</span>
    },
    {
      <span style="color: #9cdcfe;">"idemail"</span>: <span style="color: #b5cea8;">2</span>,
      <span style="color: #9cdcfe;">"destinatario"</span>: <span style="color: #ce9178;">"usuario@example.com"</span>,
      <span style="color: #9cdcfe;">"assunto"</span>: <span style="color: #ce9178;">"Recuperação de Senha"</span>,
      <span style="color: #9cdcfe;">"status"</span>: <span style="color: #ce9178;">"enviado"</span>,
      <span style="color: #9cdcfe;">"data_envio"</span>: <span style="color: #ce9178;">"2025-11-09 15:12:45"</span>,
      <span style="color: #9cdcfe;">"idsistema"</span>: <span style="color: #b5cea8;">1</span>
    }
  ],
  <span style="color: #9cdcfe;">"error"</span>: <span style="color: #569cd6;">false</span>
}</code></pre>
        </div>
    </div>
</div>