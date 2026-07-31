# Atletas, Responsáveis e Documentos

## Escopo

A Etapa 5 cria o cadastro estrutural de atletas sem exigir inscrição em campeonato. O cadastro cobre dados esportivos, equipe atual, status, posições, responsável legal de menor, documentos privados, revisão e histórico básico. Inscrições, partidas, escalações, disciplina e portal público são módulos integrados ao fluxo geral e não bloqueiam o cadastro inicial.

## Atleta

`athletes` armazena nome completo, nome esportivo, foto privada, nascimento, gênero quando aplicável, posição principal, número preferido, pé dominante, status, observações privadas, autor e datas. A idade é calculada a partir do nascimento e a exclusão usa `deleted_at` com status `archived`.

Status permitidos: `draft`, `active`, `inactive`, `blocked`, `transferred` e `archived`.

O atleta pertence a uma equipe existente e a validação usa a categoria do campeonato da equipe. Para `sub-15-masculino`, por exemplo, a idade e validada no intervalo de 12 a 15 anos e o gênero deve ser masculino. A inscrição não e consultada no cadastro.

## Posições

O seed cria 13 posições: goleiro, zagueiro, lateral direito, lateral esquerdo, ala direito, ala esquerdo, volante, meio-campista, meia ofensivo, ponta direita, ponta esquerda, segundo atacante e centroavante. Cada registro tem código, nome, grupo posicional, ordem e status. Um atleta possui uma principal e pode possuir várias secundárias, sem repetir a principal.

## Responsável legal

Atletas menores exigem um responsável no mesmo fluxo de cadastro. O vínculo registra nome, parentesco, telefone, e-mail, autorização, observações e status. O documento informado pelo responsável é cifrado com AES-256-GCM; a interface nunca o exibe em texto. Endereço não foi incluído porque não está previsto no PRD atual.

## Documentos

Tipos configuráveis seedados: documento do atleta, autorização do responsável, comprovante, atestado, foto e outros. Cada documento referência atleta, responsável quando aplicável e tipo, além de arquivo, validade, observação, motivo de rejeição, analisador e data da análise.

Status: `pending`, `approved`, `rejected`, `expired`, `replaced` e `archived`.

Arquivos ficam em armazenamento privado, fora de rotas públicas. O servidor valida erro de upload, tamanho máximo de 10 MB, MIME real por `finfo`, extensao coerente e rejeita executáveis. A leitura exige sessão, permissão e escopo do atleta.

## Acesso

- administrador: todos os atletas autorizados pelo sistema;
- organizador: atletas das equipes dos campeonamentos autorizados;
- treinador/gestor: atletas da própria equipe vinculada;
- operador e comunicação: `403`;
- documentos e dados pessoais: somente em rotas administrativas autorizadas.

## Interface estrutural

As páginas são `/admin/atletas`, `/admin/atletas/nova`, `/admin/atletas/{id}`, `/admin/atletas/{id}/editar`, `/admin/atletas/{id}/responsaveis`, `/admin/atletas/{id}/documentos` e `/admin/posicoes`. O detalhe separa dados esportivos, equipe, posições, responsável, documentos e atalhos para inscrições, partidas e disciplina.

UI/UX definitiva, campo visual, inscrições e integração com partidas permanecem para rodadas posteriores.
