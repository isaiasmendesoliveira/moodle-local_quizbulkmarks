# Bulk Quiz Question Values

<p align="center">
  <img
    src="../docs/images/bulk-quiz-question-values-logo.png"
    alt="Activity Date Status"
    width="320">
</p>

**Bulk Quiz Question Values** (`local_quizbulkmarks`) é um plugin local para o Moodle LMS que permite aos professores selecionar várias questões no mesmo questionário e atribuir o mesmo valor máximo a todas as questões selecionadas em uma única operação, reduzindo edições manuais repetitivas e mantendo a lógica nativa de questionários e notas do Moodle.

> **Versão pública:** 1.0.0  
> **Moodle:** 4.5–5.2  
> **Licença:** GNU GPL v3 ou posterior

Documentação: [English](../README.md) | **Português (Brasil)** | [Español](README.es.md)

Documentação técnica: **[Architecture and implementation](TECHNICAL.pt-BR.md)**

## Por que este plugin?

O Moodle já permite que os professores definam o valor máximo de cada questão em um questionário. No entanto, quando um questionário contém muitas questões, atribuir ou alterar esses valores individualmente pode tornar-se uma tarefa repetitiva e demorada.

O Bulk Quiz Question Values simplifica esse fluxo de trabalho ao permitir que os professores selecionem várias questões e apliquem o mesmo valor máximo (`maxmark`) a todas elas de uma única vez.

Por exemplo, em um questionário com 40 questões, um professor pode desejar que as questões 1–20 valham **0,25 ponto cada** e as questões 21–40 valham **0,50 ponto cada**. Em vez de editar 40 questões individualmente, cada grupo pode ser configurado em uma única operação.

O principal objetivo é melhorar a **produtividade do professor**, reduzir tarefas repetitivas, diminuir o risco de erros de configuração e permitir que os professores dediquem mais tempo ao planejamento das avaliações e ao ensino, em vez de tarefas rotineiras de configuração de questionários.

## Principais recursos

- Seleção de várias questões no mesmo questionário.
- Seleção de todas as questões elegíveis de uma só vez.
- Seleção de questões por intervalo numérico.
- Seleção ou desmarcação de uma questão ao clicar em qualquer ponto da linha.
- Seleção de linhas acessível por teclado.
- Atribuição do mesmo valor máximo a todas as questões selecionadas.
- Exibição do valor máximo atual de cada questão.
- Exibição dos tipos de questão de acordo com o idioma ativo no Moodle.
- Exibição em tempo real da quantidade de questões selecionadas.
- Exibição do valor total atual das questões selecionadas.
- Visualização prévia do novo valor total do questionário antes da aplicação das alterações.
- Acesso direto pela página padrão do Moodle: **Questionário → Questões**.
- Recálculo automático dos valores totais do questionário e das notas.
- Utilização das APIs nativas de questionário do Moodle, sem atualizações diretas no banco de dados.
- Interface multilíngue com suporte a inglês, português do Brasil e espanhol.
- Nenhum serviço externo ou dependência adicional durante de execução.

## Como funciona

O plugin atua nos slots de questões de um questionário específico do Moodle.

Quando o professor aplica um novo valor, o plugin atualiza o valor máximo de cada slot selecionado utilizando a API nativa de questionários do Moodle:

```php
$structure->update_slot_maxmark($slot, $newmaxmark);
```

Após a atualização das questões selecionadas, utilizam-se os mecanismos nativos de avaliação do Moodle para recalcular os valores totais do questionário, as tentativas, as notas finais e as informações do livro de notas.

O plugin **não** modifica diretamente a tabela `quiz_slots` por meio de SQL.

O Moodle continua responsável pelas tentativas do questionário, pela avaliação, pelo comportamento das questões, pelos cálculos de notas, pelas permissões e pelo livro de notas.

## Fluxo de trabalho do professor

Na página padrão de edição do questionário do Moodle:

**Questionário → Questões**

o professor tem acesso à ação **Definir valores das questões em grupo**.

A interface do plugin apresenta:

- identificação do questionário;
- quantidade total de questões;
- valor total atual do questionário;
- seleção por intervalo de questões;
- selecionar todas;
- desmarcar todas;
- seleção individual de questões;
- valor atual da questão;
- tipo de questão localizado;
- campo para definição do novo valor máximo;
- quantidade de questões selecionadas;
- valor total das questões selecionadas;
- novo valor total projetado do questionário;
- ação para aplicar o valor.

Somente as questões selecionadas são alteradas.

## Casos de uso comuns

### Valores diferentes para grupos de questões

Um questionário contém 40 questões:

```text
Questões 1–20  → 0,25 ponto cada
Questões 21–40 → 0,50 ponto cada
```

O professor pode selecionar cada intervalo e atribuir seu respectivo valor em duas operações, em vez de editar individualmente as 40 questões.

### Padronização dos valores das questões

Um professor importa ou adiciona várias questões a um questionário e deseja que todas as questões selecionadas tenham o mesmo valor máximo.

As questões podem ser selecionadas em conjunto e atualizadas em uma única operação.

### Correção de valores do questionário

Se várias questões tiverem sido configuradas com um valor incorreto, o professor pode selecionar apenas essas questões e corrigi-las simultaneamente.

### Grandes conjuntos de questões

O plugin é especialmente útil em questionários com dezenas ou centenas de questões, nos quais a edição individual de valores exigiria um trabalho repetitivo significativo.

## Comportamento em relação ao banco de questões

O Bulk Quiz Question Values atua sobre questões **dentro de um questionário específico**.

Ele altera o valor máximo (`maxmark`) atribuído ao slot da questão nesse questionário.

Ele **não** modifica a questão original armazenada no banco de questões do Moodle.

Isso significa que a mesma questão pode apresentar valores diferentes em questionários distintos, sem alterar a questão original.

Por exemplo:

```text
Questionário A → Valor da questão: 0,25
Questionário B → Valor da questão: 1,00
Questionário C → Valor da questão: 2,00
```

## Instalação

### Por arquivo ZIP

1. Baixe o arquivo ZIP da versão.
2. No Moodle, acesse **Administração do site → Plugins → Instalar plugins**.
3. Envie o arquivo ZIP e conclua a validação.
4. Acesse **Administração do site → Notificações** para concluir a instalação.
5. Limpe os caches do Moodle, se necessário.

### Pelo Git

Clone o repositório em `local/quizbulkmarks`:

```bash
git clone <repository-url> local/quizbulkmarks
```

Em seguida, acesse:

**Administração do site → Notificações**

Para concluir a instalação.

## Uso

1. Abra um questionário no Moodle.
2. Acesse **Questões**.
3. Clique em **Definir valores das questões em grupo**.
4. Selecione as questões que deseja alterar.
5. Informe o novo valor máximo.
6. Confira o novo valor total projetado do questionário.
7. Clique em **Aplicar valor às questões selecionadas**.

O plugin atualiza apenas as questões selecionadas.

## Permissões

O acesso ao plugin requer a capacidade do Moodle:

```text
mod/quiz:manage
```

Usuários sem permissão para gerenciar o questionário não podem alterar os valores das questões por meio do plugin.

## Compatibilidade

A versão pública 1.0.0 declara suporte ao Moodle **de 4.5 a 5.2**.

O GitHub Actions está configurado para validar as versões suportadas do Moodle utilizando o Moodle Plugin CI.

O plugin foi desenvolvido para funcionar com a atividade padrão Questionário (`mod_quiz`) do Moodle.

## Acessibilidade

A interface foi desenvolvida para integrar-se às convenções de acessibilidade do Moodle e do Bootstrap 5.

- A seleção das questões utiliza caixas de seleção padrão.
- Linhas inteiras das questões podem ser clicadas para selecioná-las ou desmarcá-las.
- As linhas das questões oferecem suporte à interação por teclado.
- Elementos interativos utilizam controles HTML semânticos.
- Rótulos e atributos ARIA são utilizados quando apropriado.
- O estado de seleção não é comunicado apenas por meio de cores.
- A tipografia e as convenções visuais do Moodle/tema são herdadas em vez de substituídas.
- Os nomes dos tipos de questões são exibidos usando as strings localizadas do idioma no Moodle.

## Privacidade

O plugin não cria tabelas próprias no banco de dados nem armazena dados pessoais dos usuários.

Ele atua nas configurações de questionários e nas informações de slots de questões já existentes no Moodle.

O plugin não envia informações para serviços externos.

## Idiomas

A distribuição no GitHub inclui:

- Inglês (`en`);
- Português do Brasil (`pt_br`);
- Espanhol (`es`).

A interface acompanha automaticamente o idioma ativo no Moodle.

Os nomes dos tipos de questões também são obtidos a partir dos próprios pacotes de idioma do Moodle, quando disponíveis.

## Desenvolvimento

O repositório inclui configuração do Moodle Plugin CI para validação automatizada nas versões suportadas do Moodle.

Os arquivos JavaScript AMD são compilados pelo fluxo padrão do Grunt do Moodle.

Consulte [CONTRIBUTING.md](../CONTRIBUTING.md) para informações sobre desenvolvimento e contribuições.

## Suporte e problemas

Relatos de bugs, problemas de compatibilidade e solicitações de novos recursos devem ser enviados pelo sistema de Issues do repositório no GitHub.

Ao relatar um problema, informe:

- versão do Moodle;
- versão do PHP;
- tipo e versão do banco de dados;
- versão do plugin;
- passos para reproduzir o problema;
- informações de depuração relevantes.

## Licença

GNU General Public License v3 ou posterior.

Consulte [LICENSE](../LICENSE).
