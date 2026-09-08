# Bulk Quiz Question Values — Documentação técnica

**Bulk Quiz Question Values** (`local_quizbulkmarks`) é um plugin local para Moodle LMS que permite ao professor atribuir o mesmo valor máximo a várias questões do mesmo questionário em uma única operação.

Este documento descreve o funcionamento do plugin, sua integração com o Questionário do Moodle, fluxo de dados, validação, segurança, recálculo de notas, JavaScript, acessibilidade, internacionalização, privacidade, testes e manutenção.

> **Plugin:** `local_quizbulkmarks`  
> **Versão pública:** 1.0.0  
> **Moodle:** 4.5–5.2  
> **Licença:** GNU GPL v3 ou posterior

---

## 1. Finalidade

No Moodle, o valor máximo de cada questão de um questionário normalmente é ajustado individualmente. Em questionários extensos, esse processo pode se tornar repetitivo.

O plugin permite:

- selecionar várias questões do mesmo questionário;
- selecionar todas as questões avaliáveis;
- selecionar por intervalo de números;
- informar um único novo valor máximo;
- visualizar previamente o novo total;
- aplicar o valor a todas as questões selecionadas de uma só vez.

O objetivo principal é aumentar a **produtividade do professor**, reduzir trabalho repetitivo e diminuir inconsistências manuais.

---

## 2. O que o plugin altera

O plugin altera o **valor máximo do slot da questão dentro de um questionário específico**. No Moodle, esse valor é o `maxmark`.

```text
Questão do banco de questões
        │
        ├── Questionário A → maxmark = 0.25
        ├── Questionário B → maxmark = 1.00
        └── Questionário C → maxmark = 2.00
```

A questão original do banco de questões não é modificada. Assim, a mesma questão pode ter valores diferentes em questionários diferentes.

---

## 3. O que o plugin não faz

O Bulk Quiz Question Values não:

- altera a nota padrão da questão no banco de questões;
- cria ou edita questões;
- altera conteúdo ou comportamento das questões;
- altera métodos de avaliação do questionário;
- altera diretamente a nota final do questionário;
- ignora permissões do Moodle;
- atualiza `quiz_slots` por SQL personalizado;
- cria tabelas próprias;
- armazena dados pessoais;
- envia dados a serviços externos.

O Moodle permanece como fonte de verdade para estrutura, tentativas, avaliação, permissões e livro de notas.

---

## 4. Requisitos e compatibilidade

A versão 1.0.0 declara:

```php
$plugin->requires = 2024100700; // Moodle 4.5.
$plugin->supported = [405, 502];
$plugin->maturity = MATURITY_STABLE;
```

Compatibilidade declarada:

- Moodle 4.5;
- Moodle 5.0;
- Moodle 5.1;
- Moodle 5.2.

O plugin trabalha especificamente com:

```text
mod_quiz
```

e exige a capacidade:

```text
mod/quiz:manage
```

---

## 5. Fluxo de trabalho do professor

1. Abrir um Questionário.
2. Acessar **Questões**.
3. Clicar em **Definir valores das questões em grupo**.
4. Selecionar uma ou mais questões avaliáveis.
5. Informar o novo valor máximo.
6. Conferir o resumo e o total projetado.
7. Aplicar o valor.
8. Retornar à página padrão de Questões.

Somente os slots avaliáveis selecionados são modificados.

---

## 6. Interface do usuário

O editor em grupo é implementado em:

```text
index.php
```

A página utiliza APIs de saída do Moodle e classes compatíveis com Bootstrap.

### 6.1 Informações introdutórias

O primeiro bloco explica que a operação altera o `maxmark` individual das questões selecionadas.

### 6.2 Identificação do questionário

São exibidos:

- nome do questionário;
- quantidade de questões avaliáveis;
- total atual dos valores das questões.

A soma atual é disponibilizada ao JavaScript por `data-raw-sum`.

### 6.3 Seleção das questões

Há três formas de seleção:

- seleção individual por checkbox;
- **Selecionar todas**;
- seleção por intervalo.

Exemplo:

```text
De:  1
Até: 20
```

Intervalos invertidos, como `20 → 1`, são normalizados para `1 → 20`.

### 6.4 Itens não avaliáveis

Itens como **Descrição** são exibidos, mas não podem ser selecionados.

### 6.5 Definição do valor

O campo numérico utiliza:

```text
min = 0
step = 0.01
```

### 6.6 Resumo dinâmico

Antes do envio, o plugin mostra:

- quantidade selecionada;
- total atual das questões selecionadas;
- novo total projetado.

```text
total projetado
    = soma atual
    - total atual das questões selecionadas
    + (quantidade selecionada × novo valor)
```

O total projetado é exibido com duas casas decimais. O cálculo definitivo ocorre no servidor.

---

## 7. Integração com a página nativa de Questões

O plugin não substitui a página de edição do Questionário. Ele adiciona um botão à barra de ações existente.

Arquivos envolvidos:

```text
lib.php
classes/local/edit_page_integration.php
amd/src/editbutton.js
```

### 7.1 Callbacks

`lib.php` utiliza:

```php
local_quizbulkmarks_extend_navigation()
local_quizbulkmarks_extend_settings_navigation()
```

Ambos delegam para:

```php
\local_quizbulkmarks\local\edit_page_integration::register();
```

Uma flag estática `$registered` evita registro duplicado.

### 7.2 Detecção da página

A integração verifica:

```text
mod-quiz-edit
```

e também:

```text
/mod/quiz/edit.php
```

### 7.3 Capacidade

Antes de inserir o botão:

```php
has_capability('mod/quiz:manage', $context)
```

### 7.4 Botão AMD

O PHP registra:

```php
$PAGE->requires->js_call_amd(
    'local_quizbulkmarks/editbutton',
    'init',
    [...]
);
```

O JavaScript procura:

```text
.mod_quiz-edit-action-buttons
```

e cria um link com:

```text
btn btn-secondary ms-1
```

Os textos visível e ARIA vêm das strings de idioma do Moodle.

### 7.5 Fallback para temas

Um `MutationObserver` observa a página por até cinco segundos quando a barra é renderizada tardiamente.

ID usado para impedir duplicação:

```text
local-quizbulkmarks-edit-button
```

---

## 8. Ciclo da requisição no servidor

Endpoint principal:

```text
/local/quizbulkmarks/index.php?cmid=<course-module-id>
```

### 8.1 Resolução do módulo

```php
$cmid = required_param('cmid', PARAM_INT);
```

O plugin resolve módulo, curso, questionário e contexto.

### 8.2 Autenticação e autorização

```php
require_login($course, false, $cm);
require_capability('mod/quiz:manage', $context);
```

A URL direta não contorna permissões.

### 8.3 Leitura

```php
$quizobj = quiz_settings::create($quiz->id);
```

O objeto é entregue a:

```php
\local_quizbulkmarks\local\question_value_manager
```

### 8.4 Escrita

Quando `action=apply` é enviado:

```php
require_sesskey();
```

IDs usam:

```php
PARAM_INT
```

e o valor usa:

```php
PARAM_LOCALISEDFLOAT
```

---

## 9. Serviço `question_value_manager`

Implementado em:

```text
classes/local/question_value_manager.php
```

Classe:

```php
\local_quizbulkmarks\local\question_value_manager
```

Responsabilidades principais:

```text
get_question_rows()
apply_value()
```

---

## 10. Leitura dos slots

```php
$structure = $this->quizobj->get_structure();
$structure->get_slots();
```

Para cada slot:

```php
$question = $structure->get_question_in_slot($slot->slot);
```

Dados retornados:

```text
slotid
slotnumber
page
questionid
name
qtype
qtypename
maxmark
gradable
```

### 10.1 Detecção de avaliabilidade

O plugin consulta o Moodle:

```php
$structure->is_real_question($slot->slot)
```

e não deduz a avaliabilidade pelo nome do tipo.

---

## 11. Tipos de questão localizados

Exemplos de identificadores internos:

```text
multichoice
truefalse
shortanswer
numerical
```

O plugin tenta:

```php
$component = 'qtype_' . $qtype;
get_string('pluginname', $component);
```

Como fallback:

```php
\question_bank::get_qtype_name($qtype)
```

Se necessário, retorna o qtype interno. O nome exibido acompanha o idioma ativo do Moodle quando a tradução existe.

---

## 12. Aplicação do novo valor máximo

Operação principal:

```php
$manager->apply_value($slotids, $newmaxmark);
```

### 12.1 Validação do valor

Valores negativos, infinitos ou não finitos são rejeitados:

```php
if ($newmaxmark < 0 || !is_finite($newmaxmark)) {
    throw new \invalid_parameter_exception(...);
}
```

`0` é válido.

### 12.2 Normalização dos IDs

```php
array_map('intval', $slotids)
array_unique(...)
array_values(...)
```

Isso evita processamento repetido de IDs duplicados.

### 12.3 Pertencimento ao questionário

```php
$validslots = $structure->get_slots();
```

Somente slots pertencentes ao questionário atual são processados.

### 12.4 Itens não avaliáveis

O servidor verifica novamente:

```php
if (!$structure->is_real_question($slot->slot)) {
    continue;
}
```

A segurança não depende do checkbox desabilitado no navegador.

### 12.5 Atualização pela API do Moodle

```php
$structure->update_slot_maxmark($slot, $newmaxmark);
```

Não há SQL direto. A contagem de alterações aumenta apenas quando ocorre uma mudança real.

---

## 13. Transação de banco de dados

A operação em grupo ocorre dentro de:

```php
$transaction = $DB->start_delegated_transaction();
```

e termina com:

```php
$transaction->allow_commit();
```

Atualizações e recálculo ficam agrupados no mesmo fluxo transacional.

---

## 14. Recálculo do questionário e das notas

Se ao menos um slot mudar, o plugin executa:

### 14.1 Excluir prévias

```php
quiz_delete_previews($quiz);
```

### 14.2 Recalcular a soma do questionário

```php
$gradecalculator->recompute_quiz_sumgrades();
```

### 14.3 Recalcular tentativas

```php
$gradecalculator->recompute_all_attempt_sumgrades();
```

### 14.4 Recalcular notas finais

```php
$gradecalculator->recompute_all_final_grades();
```

### 14.5 Atualizar o livro de notas

```php
quiz_update_grades($quiz, 0, true);
```

### 14.6 Frequência

O recálculo é executado **uma única vez após todos os slots selecionados serem processados**, e não uma vez por questão.

---

## 15. Quando não há mudanças

Se nenhum valor precisar ser alterado, `apply_value()` retorna `0` e o usuário recebe uma notificação informativa.

Se houver alterações, a mensagem de sucesso informa a quantidade de questões modificadas.

---

## 16. JavaScript no cliente

Fontes:

```text
amd/src/
```

Builds:

```text
amd/build/
```

Módulos:

```text
editbutton.js
selection.js
```

---

## 17. `editbutton.js`

Responsável pela integração do botão à página nativa. Ele:

- localiza `.mod_quiz-edit-action-buttons`;
- evita duplicação;
- cria o link;
- aplica classes Moodle/Bootstrap;
- recebe textos traduzidos do PHP;
- usa `MutationObserver` como fallback.

Ele não altera notas.

---

## 18. `selection.js`

Responsável por:

- selecionar/desmarcar todas;
- selecionar por intervalo;
- clique na linha;
- seleção via teclado;
- destaque da seleção;
- quantidade selecionada;
- total atual selecionado;
- total projetado.

### 18.1 Checkboxes válidos

```javascript
document.querySelectorAll('.quizbulkmarks-slot:not(:disabled)')
```

### 18.2 Linha selecionada

```text
table-active
```

### 18.3 Teclado

```text
Enter
Espaço
```

alternam a seleção.

### 18.4 Entrada numérica localizada

A prévia JavaScript aceita vírgula ou ponto. No servidor, `PARAM_LOCALISEDFLOAT` é a referência definitiva.

---

## 19. Modelo de segurança em camadas

A escrita é protegida por:

- sessão autenticada;
- resolução do módulo;
- `mod/quiz:manage`;
- `sesskey`;
- IDs inteiros;
- valor localizado;
- valor finito e não negativo;
- verificação de pertencimento ao questionário;
- verificação de avaliabilidade.

Manipular o formulário no navegador não concede novas permissões.

---

## 20. Proteção CSRF

O formulário inclui:

```php
sesskey()
```

e a escrita exige:

```php
require_sesskey();
```

---

## 21. Segurança da saída

Conteúdo dinâmico utiliza mecanismos do Moodle, incluindo:

```php
format_string(...)
s(...)
```

A marcação é produzida com `html_writer`, reduzindo concatenação direta de HTML não confiável.

---

## 22. Acessibilidade

A interface inclui:

- títulos e seções semânticos;
- checkboxes HTML;
- labels explícitos;
- ARIA quando necessário;
- `aria-labelledby`;
- `aria-describedby`;
- `aria-live="polite"`;
- seleção de linhas por teclado;
- foco visível;
- seleção não comunicada somente por cor;
- tipografia nativa Moodle/Bootstrap.

Linhas clicáveis recebem:

```text
tabindex="0"
```

e há estilo `:focus-visible`.

---

## 23. Design responsivo

São utilizadas classes como:

```text
col-12
col-6
col-lg-2
col-lg-4
row
g-3
g-4
table-responsive
```

Controles empilham em telas menores e usam colunas em telas maiores.

---

## 24. Internacionalização

Idiomas incluídos:

```text
en     Inglês
pt_br  Português do Brasil
es     Espanhol
```

Arquivos:

```text
lang/en/local_quizbulkmarks.php
lang/pt_br/local_quizbulkmarks.php
lang/es/local_quizbulkmarks.php
```

A interface utiliza:

```php
get_string(..., 'local_quizbulkmarks')
```

Os tipos de questão também acompanham o pacote de idioma ativo do Moodle.

---

## 25. Privacidade

Provider:

```text
classes/privacy/provider.php
```

Implementação:

```php
\core_privacy\local\metadata
ull_provider
```

O plugin declara que não armazena dados pessoais próprios.

---

## 26. Armazenamento de dados

O plugin não cria tabelas próprias e não mantém armazenamento persistente para:

- seleções;
- histórico de edições em grupo;
- dados pessoais;
- cópias dos valores das questões.

Os valores permanecem na configuração nativa do Questionário.

---

## 27. Estrutura de arquivos

```text
local/quizbulkmarks/
├── amd/
│   ├── build/
│   │   ├── editbutton.min.js
│   │   └── selection.min.js
│   └── src/
│       ├── editbutton.js
│       └── selection.js
├── classes/
│   ├── local/
│   │   ├── edit_page_integration.php
│   │   └── question_value_manager.php
│   └── privacy/
│       └── provider.php
├── docs/
├── lang/
│   ├── en/
│   ├── es/
│   └── pt_br/
├── tests/
│   └── question_value_manager_test.php
├── index.php
├── lib.php
├── styles.css
├── version.php
├── README.md
├── CHANGELOG.md
├── CONTRIBUTING.md
├── SECURITY.md
└── LICENSE
```

### Arquivos principais

| Arquivo | Responsabilidade |
| --- | --- |
| `index.php` | Página, requisição, validação e interface |
| `lib.php` | Callbacks de integração |
| `classes/local/edit_page_integration.php` | Detecção da página, capacidade e registro AMD |
| `classes/local/question_value_manager.php` | Leitura, validação, atualização de `maxmark` e recálculo |
| `amd/src/editbutton.js` | Botão na barra do questionário |
| `amd/src/selection.js` | Seleção e totais dinâmicos |
| `styles.css` | Estilos mínimos de linha e foco |
| `classes/privacy/provider.php` | Privacy API |
| `tests/question_value_manager_test.php` | Testes PHPUnit |
| `version.php` | Versão e compatibilidade |

---

## 28. Visão geral do fluxo de dados

```text
Professor abre Questionário → Questões
          ↓
Callbacks de local_quizbulkmarks
          ↓
edit_page_integration verifica página + capacidade
          ↓
editbutton AMD é registrado
          ↓
Botão de edição em grupo aparece
          ↓
/local/quizbulkmarks/index.php?cmid=...
          ↓
Login + capacidade
          ↓
quiz_settings::create()
          ↓
question_value_manager::get_question_rows()
          ↓
Editor em grupo
```

Fluxo de escrita:

```text
Selecionar questões
        ↓
Informar novo valor
        ↓
Prévia JavaScript
        ↓
POST action=apply + sesskey
        ↓
Validação no servidor
        ↓
question_value_manager::apply_value()
        ↓
Transação
        ├── validar pertencimento
        ├── validar avaliabilidade
        └── update_slot_maxmark()
        ↓
Se houve alteração
        ├── excluir prévias
        ├── recalcular quiz
        ├── recalcular tentativas
        ├── recalcular notas finais
        └── atualizar livro de notas
        ↓
Commit
        ↓
Notificação
```

---

## 29. Exemplo com 40 questões

```text
Questões 1–20  = 0,25 cada
Questões 21–40 = 0,50 cada
```

Primeira operação: selecionar `1 → 20` e aplicar `0,25`.

Segunda operação: selecionar `21 → 40` e aplicar `0,50`.

Assim, 40 edições individuais são substituídas por duas operações em grupo.

---

## 30. Tentativas existentes

Após mudança real:

```php
$gradecalculator->recompute_all_attempt_sumgrades();
$gradecalculator->recompute_all_final_grades();
quiz_update_grades($quiz, 0, true);
```

Portanto, a alteração não é apenas visual: tentativas e notas finais passam pelo recálculo do Moodle.

Professores e administradores devem respeitar as políticas institucionais ao alterar valores após os estudantes já terem realizado a avaliação.

---

## 31. Erros e notificações

### Nenhuma questão selecionada
A requisição é rejeitada com notificação de erro.

### Valor inválido
Valores ausentes, malformados, negativos ou não finitos são rejeitados.

### Nenhuma alteração real
É exibida notificação informativa.

### Atualização bem-sucedida
A notificação informa quantas questões foram alteradas.

---

## 32. Testes automatizados

Arquivo principal:

```text
tests/question_value_manager_test.php
```

### 32.1 Somente slots selecionados

Verifica que apenas o slot selecionado muda e que a contagem de alterações está correta.

### 32.2 Itens não avaliáveis

Usa um item Descrição para verificar que ele não é alterado e que a contagem permanece zero.

### 32.3 Valores negativos

Verifica a geração de:

```php
\invalid_parameter_exception
```

---

## 33. Moodle Plugin CI

O repositório usa:

```text
moodlehq/moodle-plugin-ci
```

O CI executa:

- instalação;
- PHP lint;
- Moodle Code Checker;
- PHPDoc;
- validação do plugin;
- upgrade savepoint;
- lint/build de JavaScript;
- PHPUnit.

A matriz cobre Moodle 4.5, 5.0, 5.1 e 5.2, com PostgreSQL, MariaDB e múltiplas versões suportadas do PHP.

---

## 34. Build AMD

Código-fonte:

```text
amd/src/
```

Arquivos gerados:

```text
amd/build/
```

O repositório inclui workflow para reconstrução dos assets AMD. Desenvolvedores devem editar `amd/src`, nunca os arquivos minificados manualmente.

---

## 35. Princípios de desenvolvimento

### Moodle como autoridade
Estrutura e notas são manipuladas por APIs do Moodle.

### Validação no servidor
JavaScript melhora a UX, mas não define permissões.

### Persistência mínima
O plugin não duplica dados do Questionário.

### Interferência mínima no tema
São usadas classes Moodle/Bootstrap e CSS reduzido.

### Localização por padrão
Textos usam String API e componentes de idioma.

### Um recálculo por operação
Todos os slots são processados antes de uma única sequência de recálculo.

---

## 36. Desempenho

Para `N` questões selecionadas:

```text
validar seleção
    ↓
processar N slots
    ↓
executar uma sequência de recálculo
```

O custo final depende do volume de tentativas e do recálculo normal do Moodle. Questionários muito grandes devem ser alterados com o mesmo cuidado usado em mudanças nativas da estrutura de avaliação.

---

## 37. Instalação

### ZIP

1. Baixar o ZIP.
2. Acessar **Administração do site → Plugins → Instalar plugins**.
3. Enviar o pacote.
4. Concluir a validação.
5. Finalizar a instalação.
6. Acessar **Administração do site → Notificações**, se necessário.

Diretório esperado:

```text
local/quizbulkmarks
```

### Git

```bash
git clone <repository-url> local/quizbulkmarks
```

Depois:

```text
Administração do site → Notificações
```

---

## 38. Atualizações

A versão 1.0.0 não possui tabelas próprias nem migrações de dados do plugin.

A atualização normalmente consiste em substituir o código pela nova versão e permitir que o Moodle detecte o novo número de versão.

Siga os procedimentos institucionais de backup, manutenção e implantação.

---

## 39. Desinstalação e impacto nos dados

A desinstalação remove a interface de edição em grupo e o botão de integração.

Os `maxmark` já aplicados permanecem na configuração nativa do Questionário e não são revertidos.

---

## 40. Solução de problemas

### Botão não aparece

Verifique:

- instalação e habilitação;
- atividade `mod_quiz`;
- página de Questões;
- capacidade `mod/quiz:manage`;
- caches;
- compatibilidade da barra de edição do tema.

### Erro de permissão

O plugin executa:

```php
require_capability('mod/quiz:manage', $context);
```

### Descrição não pode ser selecionada

É esperado: itens não avaliáveis são desabilitados.

### Total projetado inesperado

A prévia no navegador é informativa; o recálculo no servidor é definitivo.

### Notas mudaram

É esperado, pois o plugin solicita recálculo de tentativas, notas finais e livro de notas.

---

## 41. Segurança para mantenedores

Ao estender o plugin:

- mantenha `require_login()`;
- mantenha `require_capability('mod/quiz:manage', ...)`;
- mantenha `require_sesskey()`;
- valide IDs pelas APIs do Moodle;
- valide pertencimento do slot;
- valide avaliabilidade no servidor;
- prefira APIs do Questionário a SQL direto;
- escape/formate saída dinâmica;
- mantenha textos traduzidos nos arquivos de idioma.

---

## 42. Extensões futuras

Possíveis evoluções:

- novos auxiliares de seleção;
- melhorias na confirmação e prévia;
- mais testes para cenários com tentativas;
- novos idiomas;
- validação de versões futuras do Moodle.

Qualquer recurso que altere valores deve continuar usando APIs suportadas do Moodle.

---

## 43. Resumo do desenho técnico

```text
Integração com Questionário
        ↓
Botão AMD
        ↓
Editor em grupo
        ├── autenticação/capacidade
        ├── String API
        ├── UI Bootstrap acessível
        └── AMD de seleção/prévia
        ↓
question_value_manager
        ├── lê estrutura
        ├── valida slots
        ├── ignora itens não avaliáveis
        └── update_slot_maxmark()
        ↓
Calculador de notas do Moodle
        ├── sumgrades
        ├── tentativas
        ├── notas finais
        └── livro de notas
```

O plugin acrescenta produtividade ao subsistema nativo de Questionários sem substituí-lo e sem criar um modelo paralelo de avaliação.

---

## 44. Documentação relacionada

- [`README.md`](../README.md) — visão geral e uso;
- [`TECHNICAL.md`](TECHNICAL.md) — documentação técnica em inglês;
- [`TESTING.md`](TESTING.md) — testes funcionais;
- [`MARKETPLACE.md`](MARKETPLACE.md) — publicação no Moodle Marketplace;
- [`../CONTRIBUTING.md`](../CONTRIBUTING.md) — contribuições;
- [`../SECURITY.md`](../SECURITY.md) — segurança;
- [`../CHANGELOG.md`](../CHANGELOG.md) — histórico;
- [`../LICENSE`](../LICENSE) — licença.

---

## 45. Mantenedor

**Isaias Mendes de Oliveira**  
Email: **isaiasmendes@gmail.com**

---

## 46. Licença

Bulk Quiz Question Values é software livre distribuído sob a **GNU General Public License v3 ou posterior**.

Consulte [`LICENSE`](../LICENSE).
