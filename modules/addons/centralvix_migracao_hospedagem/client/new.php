<?php $panels = CentralVixMigModule::panels(); ?>
<div class="cvix-mig-hero"><h2>Nova solicitação de migração</h2><p>Preencha os dados da hospedagem atual. Nossa equipe analisará e entrará em contato.</p></div>
<form id="cvix-mig-create-form" class="cvix-mig-form cvix-mig-panel" method="post" action="<?php echo CentralVixMigSecurity::e($modulelink.'&'.CentralVixMigModule::AJAX_PARAM.'=create'); ?>">
<input type="hidden" name="csrf_token" value="<?php echo CentralVixMigSecurity::e($csrf); ?>">
<label>Domínio *<input type="text" name="domain" required placeholder="meusite.com.br"></label>
<label>Hospedagem atual<input type="text" name="current_hosting" placeholder="Nome do provedor"></label>
<label>Painel atual<select name="current_panel">
<?php foreach ($panels as $k=>$l): ?><option value="<?php echo CentralVixMigSecurity::e($k); ?>"><?php echo CentralVixMigSecurity::e($l); ?></option><?php endforeach; ?>
</select></label>
<label>Servidor atual<input type="text" name="current_server" placeholder="Opcional — hostname ou IP"></label>
<label>Tipo de migração<select name="migration_type">
<option value="completa">Completa (site + banco + e-mails)</option>
<option value="site">Somente site</option>
<option value="email">Somente e-mails</option>
<option value="banco">Somente banco de dados</option>
</select></label>
<label>Tamanho estimado<input type="text" name="estimated_size" placeholder="Ex: 2 GB"></label>
<label>Quantidade de bancos<input type="number" name="database_count" min="0" value="1"></label>
<label>Quantidade de e-mails<input type="number" name="email_count" min="0" value="0"></label>
<label>Data preferencial<input type="date" name="preferred_date"></label>
<label>Observações<textarea name="notes" rows="4" placeholder="Informações adicionais"></textarea></label>

<div class="cvix-mig-cred-box">
<h3>Credenciais de acesso (opcional)</h3>
<p class="cvix-mig-muted">Armazenadas com criptografia. A senha nunca será exibida novamente após o envio.</p>
<label class="cvix-mig-check"><input type="checkbox" name="provide_credentials" value="1" id="cvix-provide-cred"> Informar credenciais agora</label>
<div id="cvix-cred-fields" style="display:none">
<label>Host<input type="text" name="cred_host" autocomplete="off"></label>
<label>Porta<input type="number" name="cred_port" value="2083"></label>
<label>Usuário<input type="text" name="cred_username" autocomplete="off"></label>
<label>Senha<input type="password" name="cred_password" autocomplete="new-password"></label>
</div>
</div>

<button type="submit" class="cvix-mig-btn">Enviar solicitação</button>
<div id="cvix-mig-form-msg"></div>
</form>
<p><a href="<?php echo CentralVixMigSecurity::e($modulelink); ?>">← Minhas solicitações</a></p>
