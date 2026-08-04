# Checklist configurável de evidências

Cada campeonato pode ter uma lista própria de evidências da partida. Ela começa vazia: campeonatos já existentes mantêm o fluxo anterior até que um administrador inclua itens manualmente.

## Administração

Em `Campeonatos > [campeonato] > Evidências`, o administrador cria, edita, ativa, desativa, remove e restaura itens. Cada item define momento esperado, formatos MIME permitidos, mínimo/máximo de arquivos, tamanho máximo, observação obrigatória, bloqueios e presença na prestação de contas. A cópia de checklist só ocorre quando o administrador a solicita; não há alteração automática de outro campeonato.

## Operação da partida

Na central operacional, o atalho **Abrir evidências** abre a lista da partida. O operador pode enviar múltiplos arquivos, salvar observação, consultar histórico e remover itens ainda não aprovados. Imagens passam por validação real e conversão para WebP; PDFs aceitos permanecem privados. Arquivos têm nome físico aleatório, hash SHA-256 quando disponível e download por rota autorizada.

Itens do checklist ficam em revisão após o envio. Administradores aprovam ou rejeitam; rejeição exige motivo. Antes de aprovação, a evidência pode ser substituída ou removida, preservando o histórico.

## Bloqueios e exceções

O checklist só bloqueia quando o item estiver ativo, obrigatório e marcado para aquele ponto: início da operação, envio para aprovação ou conclusão documental. Salvar rascunhos e registrar dados posteriores não é bloqueado. Exceção requer permissão `evidence.override`, justificativa e gera histórico e auditoria.

## Prestação de contas

Exportação de evidências inclui apenas arquivos aprovados de partidas aprovadas. O CSV leva item de checklist, responsável, revisão, datas, observação e hash. Pendências, rejeições e exceções permanecem no histórico privado da partida para acompanhamento administrativo.

## Migração e reversão operacional

`0030_configurable_match_evidence_checklist.sql` é aditiva. Em uma reversão controlada, desative itens antes de voltar o código; não descarte arquivos nem dados. A aplicação atual não executa migrations reversas automaticamente, portanto qualquer `DROP` deve ser aprovado, testado em cópia do banco e realizado fora do fluxo normal de implantação.
