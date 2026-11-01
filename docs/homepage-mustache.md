# HTML da home com Mustache no LearnLingo

O campo **Página inicial do site > HTML da página inicial** (`homepagehtml`) aceita variáveis, condições e loops Mustache processados pelo **PHP antes da página ser enviada ao navegador**. A funcionalidade vale para os modos **HTML personalizado** e **LearnLingo** (HTML acima dos cards nativos). **Não altera** os templates Mustache nativos, o rodapé, SCSS, páginas de cursos ou o modo **Moodle**.

## Variáveis enviadas ao homepagehtml

| Variável | Tipo | Disponibilidade |
| --- | --- | --- |
| `isloggedin`, `isguest` | Booleanos | Todos |
| `site.fullname`, `site.shortname`, `site.url` | Texto | Todos |
| `loginurl`, `dashboardurl`, `mycoursesurl`, `coursesurl` | URLs | Todos |
| `user` | Objeto ou false | Somente usuário autenticado |
| `user.id`, `user.firstname`, `user.lastname`, `user.fullname`, `user.email`, `user.profileurl`, `user.lang` | Dados do próprio usuário | Somente usuário autenticado |
| `courses` | Array de cursos visíveis | Todos, com seleção pública aplicada ao visitante |
| `hascourses`, `coursecount` | Booleano / inteiro | Todos; para visitantes `coursecount` é a quantidade real na página atual |
| `mycourses` | Array dos cursos em que o usuário está matriculado | Somente usuário autenticado |
| `hasmycourses`, `mycoursecount` | Booleano / inteiro | Todos |
| `cohorts` | Array das coortes visíveis do usuário | Somente usuário autenticado |
| `hascohorts` | Booleano | Todos |
| `coursepagination` | Objeto (ou false) | Somente visitantes sem login; contém `coursesperpage`, `coursecount`, `coursetotal`, `coursecolumns`, `coursepage`, `coursepagecount`, `hascoursepages`, `coursepages`, `haspreviouscoursepage`, `hasnextcoursepage`, `previouscoursepageurl` e `nextcoursepageurl` |

Cada item de `courses` ou `mycourses` contém `id`, `fullname`, `shortname`, `summary` (texto abreviado), `categoryname`, `url`, `imageurl`, `hasimage`, `startdate`, `enddate`, `progress`, `hasprogress`, `completed` e `enrolled`. As datas são timestamps Unix. O progresso é percentual de 0 a 100; quando o acompanhamento de conclusão não está disponível, `hasprogress` é false e `progress` é 0. Apenas os cursos do loop `mycourses` carregam progresso pessoal; `courses` não representa matrícula.

Cada item de `cohorts` contém `id`, `name` e `idnumber`. Visitantes não autenticados nunca recebem dados de usuário, matrículas ou coortes, e nenhum dado de outros alunos é enviado.

## Exemplos para usar em homepagehtml

Exibir uma saudação apenas para usuários autenticados:

```html
{{#isloggedin}}
  <h2>Olá, {{user.firstname}}!</h2>
  <a href="{{mycoursesurl}}">Acessar meus cursos</a>
{{/isloggedin}}
{{^isloggedin}}
  <h2>Conheça nossos cursos</h2>
  <a href="{{loginurl}}">Entrar</a>
{{/isloggedin}}
```

Listar todos os cursos do catálogo público ou apenas os cursos selecionados para visitantes nas configurações:

```html
<section class="catalogo">
  {{#hascourses}}
    <div class="catalogo-grid">
      {{#courses}}
        <article class="curso">
          {{#hasimage}}<img src="{{imageurl}}" alt="">{{/hasimage}}
          <h3><a href="{{url}}">{{fullname}}</a></h3>
          <p>{{summary}}</p>
          <span>{{categoryname}}</span>
        </article>
      {{/courses}}
    </div>
  {{/hascourses}}
</section>
```

Exibir matrículas, progresso e coortes apenas do aluno atual:

```html
{{#isloggedin}}
  {{#hasmycourses}}
    <h2>Continue seus estudos</h2>
    {{#mycourses}}
      <a href="{{url}}">{{fullname}}</a>
      {{#hasprogress}}
        <progress value="{{progress}}" max="100">{{progress}}%</progress>
      {{/hasprogress}}
    {{/mycourses}}
  {{/hasmycourses}}
  {{#hascohorts}}
    <p>Minhas turmas:
      {{#cohorts}}<span>{{name}}</span>{{/cohorts}}
    </p>
  {{/hascohorts}}
{{/isloggedin}}
```

Mustache usa `{{#lista}}...{{/lista}}` para loops, `{{#boolean}}...{{/boolean}}` para condições e `{{^boolean}}...{{/boolean}}` para o bloco alternativo. Não existe execução de JavaScript para resolver variáveis, e não são disponibilizados helpers do Moodle, lambdas, templates parciais, SQL nem acesso a propriedades fora do contexto documentado. Strings de contexto são escapadas no servidor, inclusive com `{{{variavel}}}`, para impedir injeção de HTML por nomes ou dados de cursos.

## Seleção e ordenação de cursos para visitantes

Em **Administração > Aparência > Temas > LearnLingo > Página inicial do site**, marque quais cursos devem aparecer em `{{#courses}}` para quem não está autenticado. Se **nenhum** estiver marcado, todos os cursos visíveis e acessíveis entram na lista em ordem de classificação (`sortorder`) do Moodle. Categorias e cursos ocultos não aparecem, mesmo quando suas IDs permanecerem salvas na seleção. A configuração não matricula, não libera acesso e não afeta `mycourses`.

## Quantidade de cursos por página e grade responsiva para visitantes

A configuração **Cursos por página para visitantes sem login** (`homepagepubliccoursesperpage`) aceita valores de 1 a 12 e tem padrão **4**. A limitação é aplicada **exclusivamente ao loop `{{#courses}}` de visitantes não autenticados**. O contexto do aluno autenticado, `mycourses`, sua matrícula e seu progresso permanecem sem limites. Os cursos são ordenados pelo Moodle, filtrados pela seleção pública e divididos em páginas, usando o parâmetro `llcoursepage` na home.

O Mustache não suporta passar um argumento numérico diretamente em `{{#courses}}`; o número é escolhido na configuração ou enviado pelo MCP (`publiccoursesperpage`). Não se deve fazer quatro cards estáticos nem repetir cursos para preencher espaço. A variável `{{coursepagination.coursecolumns}}` informa a quantidade **real** de cards na página (até o máximo configurado), enquanto `{{coursepagination.coursesperpage}}` informa o limite. Exemplo: com limite 3 e 4 cursos disponíveis, o PHP entrega três na página 1 e um na página 2; com limite 4 e somente 3 cursos disponíveis, entrega três com `coursecolumns=3`.

Exemplo de HTML em `homepagehtml`:

```html
{{#isguest}}
  {{#hascourses}}
    <section class="catalogo-visitante" style="--colunas: {{coursepagination.coursecolumns}}">
      {{#courses}}
        <article class="cartao">
          {{#hasimage}}<img src="{{imageurl}}" alt="">{{/hasimage}}
          <h3><a href="{{url}}">{{fullname}}</a></h3>
        </article>
      {{/courses}}
    </section>
  {{/hascourses}}
  {{#coursepagination.hascoursepages}}
    <nav aria-label="Páginas de cursos">
      {{#coursepagination.coursepages}}
        <a href="{{url}}" {{#iscurrent}}aria-current="page"{{/iscurrent}}>{{number}}</a>
      {{/coursepagination.coursepages}}
    </nav>
  {{/coursepagination.hascoursepages}}
{{/isguest}}
```

SCSS com adaptação responsiva (em `homepagescss` e restrito à home):

```scss
.catalogo-visitante {
  display: grid;
  grid-template-columns: repeat(var(--colunas), minmax(0, 1fr));
  gap: 1.5rem;
}
@media (max-width: 767px) {
  .catalogo-visitante { grid-template-columns: 1fr; }
}
```

A lista `coursepagination.coursepages` fornece objetos `number`, `url` e `iscurrent`. Também estão disponíveis `hasnextcoursepage`, `nextcoursepageurl`, `haspreviouscoursepage` e `previouscoursepageurl` para botões de anterior/próxima página. Para usuários autenticados, `coursepagination` é falso, e nenhuma restrição é aplicada a `courses` nem a `mycourses`.

## Cache, eventos e privacidade

O catálogo **público** de visitantes é armazenado no MUC (`theme_learnlingo/publiccourses`) por até **300 segundos**, separado por idioma e seleção configurada. O cache é invalidado quando cursos ou categorias são criados, alterados, removidos ou quando um curso é restaurado; alterações de configuração pelo painel, JSON ou MCP também o limpam.

**Os dados do usuário autenticado, suas matrículas, coortes e progresso são calculados novamente a cada acesso.** Não são gravados nesse cache, o HTML final não é compartilhado entre pessoas e a saída Mustache é gerada exclusivamente para o visitante atual. Por isso, alterações de matrícula e conclusão já aparecem na próxima visita sem aguardar invalidação.

## Uso pelo ChatGPT ou Claude

A ferramenta de leitura `learnlingo_get_homepage` retorna a configuração e uma propriedade `mustache` com o contrato de variáveis, instruções para o ChatGPT/Claude, exemplos, privacidade e cache. A ferramenta geral `learnlingo_get_settings` também retorna as mesmas instruções no campo `homepage_mustache`. Os textos descritivos das ferramentas MCP de gravação (`learnlingo_update_homepage`, `learnlingo_update_settings` e `learnlingo_update_styles`) orientam o modelo a consultar essas instruções e usar Mustache em vez de fixar nomes, cursos, imagens ou progresso no HTML. A ferramenta de gravação `learnlingo_update_homepage` permite atualizar `html`, `mode`, `homepagescss`, `fullwidth` e `publiccourses` (string com IDs separados por vírgula; string vazia seleciona todos). A atualização exige prévia e confirmação, e não altera os templates Mustache nativos. Para alterar o tamanho da página pública, use `publiccoursesperpage` (string de "1" a "12"; padrão "4") no `learnlingo_update_homepage` ou `homepagepubliccoursesperpage` no `learnlingo_update_settings`. As descrições MCP explicam que a limitação é somente para visitantes e instruem a IA a usar `coursepagination.coursecolumns` e a paginação, sem inventar cards.
