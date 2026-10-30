<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Portable LearnLingo JSON configuration and ChatGPT instructions.
 *
 * @package theme_learnlingo
 * @copyright 2026 Eduardo Kraus
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace theme_learnlingo;

use invalid_parameter_exception;
use JsonException;

/**
 * Versioned, strictly validated configuration interchange.
 */
final class branding_exchange {
    /** Schema version. */
    public const SCHEMA_VERSION = 1;
    /** Maximum JSON payload size. */
    public const MAX_JSON_LENGTH = 400000;

    /**
     * All portable theme settings, with their types and default values.
     *
     * Uploaded logos, including SVG, remain in Moodle File API.
     *
     * @return array
     */
    public static function definitions(): array {
        return [
            'brandcolor' => ['color', '#58cc02'],
            'accentcolor' => ['color', '#8be24a'],
            'canvascolor' => ['color', '#f7faf5'],
            'textcolor' => ['color', '#263238'],
            'dashboardeyebrowtext' => ['text', ''],
            'dashboarddescriptiontext' => ['text', ''],
            'loginpaneltitletext' => ['text', ''],
            'loginpaneldescriptiontext' => ['text', ''],
            'loginpaneltaglinetext' => ['text', ''],
            'homepagemode' => ['homepage', 'learnlingo'],
            'homepagehtml' => ['html', ''],
            'homepagefullwidth' => ['bool', true],
            'showdashboardhero' => ['bool', true],
            'showcoursehero' => ['bool', true],
            'courseheroeyebrow' => ['text', get_string('courseheroeyebrowdefault', 'theme_learnlingo')],
            'showcourseherosummary' => ['bool', true],
            'showcourseheroprogress' => ['bool', true],
            'showpersonalxp' => ['bool', true],
            'scsspre' => ['scss', ''],
            'scss' => ['scss', ''],
        ];
    }

    /**
     * Export current site-specific settings.
     *
     * @return array
     */
    public static function export(): array {
        $settings = [];
        foreach (self::definitions() as $name => [$type, $default]) {
            $value = get_config('theme_learnlingo', $name);
            if ($value === false || $value === null) {
                $value = $default;
            }
            $settings[$name] = $type === 'bool' ? (bool) $value : (string) $value;
        }
        return [
            'schema_version' => self::SCHEMA_VERSION,
            'plugin' => 'theme_learnlingo',
            'repository' => 'https://github.com/EduardoKrausME/moodle-theme_learnlingo',
            'settings' => $settings,
        ];
    }

    /**
     * Encode the stable exchange format.
     *
     * @param array $data Document.
     * @return string
     */
    public static function json(array $data): string {
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    /**
     * Build a prompt which starts a guided conversation, not an immediate rewrite.
     *
     * @param array $data Original configuration.
     * @return string
     */
    public static function prompt(array $data): string {
        $instructions = <<<'PROMPT'
Você é um especialista em identidade visual e temas Moodle e está ajudando
a personalizar uma instalação do LearnLingo.

Plugin: theme_learnlingo
Repositório: https://github.com/EduardoKrausME/moodle-theme_learnlingo

O JSON ao final desta mensagem foi exportado de uma instalação real.
Seu objetivo é ENTREVISTAR o usuário e, somente ao final, produzir
um novo JSON com a MESMA estrutura para importação no Moodle.

COMO CONDUZIR A CONVERSA
1. Comece perguntando qual instituição será atendida e o público-alvo.
   Se essas informações já estiverem na conversa, não pergunte novamente.
   Faça perguntas naturais, normalmente uma de cada vez.
2. Investigue as cores: cor principal, destaque, fundo e texto.
   Pergunte se quer um visual claro/escuro e ofereça paletas se houver dúvidas.
3. Pergunte se ele deseja modificar a logo. Logos são arquivos
   gerenciados pelo Moodle (PNG, JPG, WebP ou SVG), não strings JSON.
   Não inclua SVG no JSON; faça upload no campo de logo global do Moodle.
4. Pergunte que home deseja: "moodle" (padrão Moodle), "learnlingo"
   (visual LearnLingo) ou "html" (página personalizada).
   No modo "html", produza uma home responsiva em settings.homepagehtml
   usando HTML sem scripts, com conteúdo adequado à instituição.
   Use settings.scss para estilos adicionais e settings.scsspre
   para variáveis SCSS se realmente necessário.
5. Pergunte se deseja alterar os textos institucionais, mensagem
   e marca do login, saudação inicial, cabeçalho de curso,
   progresso do aluno, XP e outros itens configuráveis.
   Não modifique o que o usuário quiser preservar.
6. Continue perguntando até entender o objetivo. Se o usuário não
   souber responder, proponha opções concretas e peça sua escolha.
7. Quando estiver satisfeito, resuma as decisões e confirme se
   o usuário quer fechar a configuração ou mudar mais alguma coisa.

FORMATO OBRIGATÓRIO DA RESPOSTA FINAL
- Entregue somente um bloco de código Markdown do tipo json,
  sem parágrafos antes ou depois.
- Mantenha EXATAMENTE as quatro chaves externas schema_version,
  plugin, repository e settings, e TODAS as chaves internas originais.
- Nunca acrescente, renomeie ou remova campos; preserve os tipos.
  schema_version deve continuar 1; plugin e repository não mudam.
- As quatro cores são strings "#RRGGBB".
- Campos true/false são booleanos JSON, não strings.
- HTML, SCSS e todos os demais textos são strings JSON com
  aspas e quebras de linha devidamente escapadas.
- Preserve todos os valores que não precisarem de alteração.
- Não insira segredos, PHP, JavaScript, scripts externos ou conteúdo
  executável.
- Certifique-se de que a saída seja JSON válido e reproduza
  exatamente a estrutura fornecida. O importador rejeita chaves
  ausentes, desconhecidas, tipos incorretos e SVG inseguro.

O usuário copiará apenas o conteúdo do JSON final e colará na segunda
caixa de texto da página LearnLingo, clicando em Importar configurações.

CONFIGURAÇÃO ORIGINAL:
PROMPT;
        $fence = str_repeat(chr(96), 3);
        return $instructions . "\n" . $fence . 'json' . "\n"
            . self::json($data) . "\n" . $fence;
    }

    /**
     * Parse and fully validate a returned JSON document.
     *
     * @param string $source JSON content without Markdown delimiters.
     * @return array Normalized document.
     */
    public static function parse(string $source): array {
        if (strlen($source) > self::MAX_JSON_LENGTH) {
            throw new invalid_parameter_exception(get_string('jsonerrorlarge', 'theme_learnlingo'));
        }
        try {
            $data = json_decode(trim($source), true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new invalid_parameter_exception(get_string('jsonerrorinvalid', 'theme_learnlingo'));
        }
        if (!is_array($data) || array_is_list($data) ||
                !self::same_keys($data, ['schema_version', 'plugin', 'repository', 'settings'])) {
            throw new invalid_parameter_exception(get_string('jsonerrorstructure', 'theme_learnlingo'));
        }
        if ($data['schema_version'] !== 1 || $data['plugin'] !== 'theme_learnlingo' ||
                $data['repository'] !== 'https://github.com/EduardoKrausME/moodle-theme_learnlingo') {
            throw new invalid_parameter_exception(get_string('jsonerroridentity', 'theme_learnlingo'));
        }
        $values = $data['settings'];
        if (!is_array($values) || array_is_list($values) ||
                !self::same_keys($values, array_keys(self::definitions()))) {
            throw new invalid_parameter_exception(get_string('jsonerrorsettings', 'theme_learnlingo'));
        }

        $normalized = [];
        foreach (self::definitions() as $name => [$type, $default]) {
            $value = $values[$name];
            if ($type === 'bool') {
                if (!is_bool($value)) {
                    self::invalid_field($name);
                }
            } else {
                if (!is_string($value)) {
                    self::invalid_field($name);
                }
                $limits = ['color' => 7, 'text' => 1500, 'homepage' => 20,
                    'html' => 150000, 'scss' => 80000];
                if (strlen($value) > $limits[$type]) {
                    self::invalid_field($name);
                }
                if ($type === 'color' && !preg_match('/^#[A-Fa-f0-9]{6}$/D', $value)) {
                    self::invalid_field($name);
                }
                if ($type === 'homepage' && !in_array($value, ['learnlingo', 'moodle', 'html'], true)) {
                    self::invalid_field($name);
                }
                if ($type === 'text' && (str_contains($value, '<') ||
                        str_contains($value, '>') || str_contains($value, "\0"))) {
                    self::invalid_field($name);
                }
                if (($type === 'html' || $type === 'scss') &&
                        (str_contains($value, '<?') || str_contains($value, "\0"))) {
                    self::invalid_field($name);
                }
                // No remote CSS imports or executable embeds in generated content.
                if ($type === 'scss' &&
                        preg_match('~@import\s+(?:url\s*\(|[\x22\x27]https?://)~i', $value)) {
                    self::invalid_field($name);
                }
                if ($type === 'html' &&
                        preg_match('~<\s*(script|iframe|object|embed|form)\b~i', $value)) {
                    self::invalid_field($name);
                }
            }
            $normalized[$name] = $value;
        }
        return [
            'schema_version' => 1,
            'plugin' => 'theme_learnlingo',
            'repository' => $data['repository'],
            'settings' => $normalized,
        ];
    }

    /**
     * Apply only whitelisted validated fields.
     *
     * @param array $data Validated document.
     * @return void
     */
    public static function apply(array $data): void {
        foreach (self::definitions() as $name => [$type, $default]) {
            $value = $data['settings'][$name];
            set_config($name, $type === 'bool' ? (int) $value : $value, 'theme_learnlingo');
        }
        theme_reset_all_caches();
    }

    /**
     * Compare object key sets regardless of JSON object ordering.
     *
     * @param array $object JSON object.
     * @param array $expected Expected keys.
     * @return bool
     */
    private static function same_keys(array $object, array $expected): bool {
        $keys = array_keys($object);
        sort($keys);
        sort($expected);
        return $keys === $expected;
    }

    /**
     * Reject a malformed field.
     *
     * @param string $name Field.
     * @return never
     */
    private static function invalid_field(string $name): never {
        throw new invalid_parameter_exception(get_string('jsonerrorfield', 'theme_learnlingo', $name));
    }
}
