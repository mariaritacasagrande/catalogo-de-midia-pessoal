# Catálogo de Mídia Pessoal

Plugin para WordPress que permite cadastrar filmes, livros e séries como posts convencionais (usando os templates padrão do seu tema), avaliar cada item com nota e sentimento final, e exibir na sidebar o que está sendo lido/assistido no momento.

**Versão:** 1.0.0

---

## Funcionalidades

- **Custom Post Type "Catálogo de Mídia"** — cadastro de itens com título, conteúdo, resumo, capa (imagem destacada), comentários e autor.
- **Taxonomia "Tipo de Mídia"** — organiza os itens em Filme, Livro e Série (termos criados automaticamente na ativação). Você pode adicionar novos tipos livremente.
- **Caixa de Avaliação e Progresso** (metabox no editor), com:
  - Nota de 0 a 10 (incrementos de 0,5)
  - Sentimento final (😍 Adorei, 🙂 Gostei, 😐 Neutro, 🙁 Não gostei, 😡 Detestei)
  - Status (Quero ver/ler, Em andamento, Concluído, Abandonado)
  - Data de início e data de término
- **Exibição automática** da avaliação no topo do conteúdo do post, sem precisar editar o template do tema.
- **Widget de sidebar "Em andamento agora"** — mostra os itens com status "Em andamento", ordenados pela data de início mais recente, com miniatura da capa, título, tipo e data.
- **CSS embutido** que funciona com qualquer tema, sem necessidade de folha de estilo externa.

---

## Instalação

1. Copie o arquivo do plugin para a pasta `wp-content/plugins/catalogo-midia-pessoal/` do seu WordPress (o arquivo principal deve se chamar, por exemplo, `catalogo-midia-pessoal.php`).
2. No painel do WordPress, vá em **Plugins** → **Plugins Instalados**.
3. Localize "Catálogo de Mídia Pessoal" e clique em **Ativar**.
4. Na ativação, os termos padrão da taxonomia (Filme, Livro, Série) são criados automaticamente e as URLs (rewrite rules) são atualizadas.

---

## Como usar

### Cadastrando um item

1. No menu lateral do WordPress, acesse **Catálogo de Mídia** → **Adicionar Novo**.
2. Preencha título, conteúdo/resenha, resumo (excerpt) e defina a capa (imagem destacada).
3. Selecione o **Tipo de Mídia** (Filme, Livro ou Série) na caixa de taxonomia.
4. Na caixa lateral **"Avaliação e Progresso"**, preencha:
   - Nota (0 a 10)
   - Sentimento final
   - Status atual
   - Data de início e/ou término
5. Publique o item normalmente.

O item ficará acessível publicamente em uma URL como:https://seu.blog/catalogo/nome-do-item/
