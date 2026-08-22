# Campeonato de demonstração do operador

O comando `db:seed:operator-demo` cria um cenário privado e separado da Copa Brasil de Talentos:

- campeonato `demonstracao-operador-2026`;
- dois times fictícios;
- um local, uma fase, um grupo, uma rodada e uma partida agendada;
- atribuição automática somente quando há um único operador ativo, ou por e-mail informado;
- execução idempotente: repetir o comando não duplica o cenário.

## Execução local ou no cPanel

```bash
php bin/console.php db:seed:operator-demo --confirm-demo
```

Para escolher explicitamente o operador:

```bash
php bin/console.php db:seed:operator-demo --confirm-demo --operator-email=operador@exemplo.com
```

No cPanel, execute dentro da pasta do projeto e use o PHP configurado para a hospedagem, por exemplo:

```bash
cd /home/USUARIO/public_html
/opt/cpanel/ea-php82/root/usr/bin/php bin/console.php db:seed:operator-demo --confirm-demo --operator-email=EMAIL_DO_OPERADOR
```

O comando não cria usuário nem senha. Se não houver atribuição automática, o administrador pode abrir a partida e usar **Atribuir operador**. O cenário é privado, não aparece no portal público e não entra em prestação de contas enquanto permanecer sem publicação/homologação.

## Como testar

1. Entre como operador.
2. Abra **Partidas para operar**.
3. Abra a partida do **Campeonato Demonstração do Operador 2026**.
4. Registre os eventos e finalize o fluxo conforme necessário.

## Exclusão

No fluxo normal, use **Arquivar campeonato** na página do campeonato. Isso retira o campeonato das operações ativas e preserva o histórico. A exclusão definitiva não deve ser um botão casual: partidas, documentos, auditoria e vínculos dependem desse histórico. Se for realmente necessário apagar dados, faça isso pela ferramenta administrativa de retenção/eliminação permanente, depois de um backup e com confirmação do escopo.
