<?php

namespace Database\Seeders;

use App\Models\DocumentTemplate;
use Illuminate\Database\Seeder;

class DocumentTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Sprint 14: Templates de documentos jurídicos padrão
     */
    public function run(): void
    {
        $templates = [
            // ==========================================
            // PROCURAÇÕES
            // ==========================================
            [
                'name' => 'Procuração Ad Judicia',
                'slug' => 'procuracao-ad-judicia',
                'category' => 'procuracao',
                'description' => 'Procuração padrão para representação judicial em todas as instâncias.',
                'is_system' => true,
                'content' => <<<HTML
<h2 style="text-align: center;">PROCURAÇÃO AD JUDICIA</h2>

<p><strong>OUTORGANTE:</strong> {{cliente_nome}}, inscrito(a) no CPF/CNPJ sob nº {{cliente_documento}}, residente e domiciliado(a) em {{cliente_endereco}}, telefone {{cliente_telefone}}, e-mail {{cliente_email}}.</p>

<p><strong>OUTORGADO:</strong> {{advogado_nome}}, inscrito(a) na {{advogado_oab}}, com escritório profissional situado em {{advogado_endereco}}, telefone {{advogado_telefone}}, e-mail {{advogado_email}}.</p>

<p><strong>PODERES:</strong> O(A) outorgante nomeia e constitui o(a) outorgado(a) seu(sua) bastante procurador(a), para o foro em geral, com os poderes da cláusula <em>ad judicia</em>, para representá-lo(a) em qualquer Juízo, Instância ou Tribunal, podendo propor contra quem de direito as ações competentes e defendê-lo(a) nas contrárias, seguindo umas e outras, até final decisão, usando os recursos legais e acompanhando-os, conferindo-lhe, ainda, poderes especiais para confessar, reconhecer a procedência do pedido, transigir, desistir, renunciar ao direito sobre que se funda a ação, receber, dar quitação e firmar compromisso, tudo o que for necessário ao fiel cumprimento do presente mandato.</p>

<p style="text-align: center;">{{cliente_endereco}}, {{data_atual}}.</p>

<br><br>

<p style="text-align: center;">_____________________________________________<br>
<strong>{{cliente_nome}}</strong><br>
CPF/CNPJ: {{cliente_documento}}</p>
HTML,
            ],

            [
                'name' => 'Procuração Específica Criminal',
                'slug' => 'procuracao-criminal',
                'category' => 'procuracao',
                'description' => 'Procuração para representação em processos criminais.',
                'is_system' => true,
                'content' => <<<HTML
<h2 style="text-align: center;">PROCURAÇÃO AD JUDICIA E EXTRA</h2>
<h3 style="text-align: center;">Área Criminal</h3>

<p><strong>OUTORGANTE:</strong> {{cliente_nome}}, portador(a) do RG nº ____________, inscrito(a) no CPF sob nº {{cliente_documento}}, residente e domiciliado(a) em {{cliente_endereco}}.</p>

<p><strong>OUTORGADO(A):</strong> {{advogado_nome}}, {{advogado_oab}}, com escritório em {{advogado_endereco}}.</p>

<p><strong>PODERES:</strong> Pelo presente instrumento particular de mandato, o(a) OUTORGANTE nomeia e constitui o(a) OUTORGADO(A) seu(sua) bastante procurador(a), com poderes da cláusula <em>ad judicia et extra</em>, para representá-lo(a) perante qualquer Delegacia de Polícia, Ministério Público, Juízo, Instância ou Tribunal, especialmente para atuar em processos e inquéritos de natureza criminal, podendo:</p>

<ul>
<li>Acompanhar inquéritos policiais e atos investigatórios;</li>
<li>Requerer diligências e vista dos autos;</li>
<li>Apresentar defesa prévia, memoriais e alegações finais;</li>
<li>Interpor recursos em todas as instâncias;</li>
<li>Substabelecer com ou sem reservas de poderes.</li>
</ul>

<p style="text-align: center;">{{cliente_endereco}}, {{data_atual}}.</p>

<br><br>

<p style="text-align: center;">_____________________________________________<br>
<strong>{{cliente_nome}}</strong></p>
HTML,
            ],

            // ==========================================
            // SUBSTABELECIMENTOS
            // ==========================================
            [
                'name' => 'Substabelecimento com Reserva de Poderes',
                'slug' => 'substabelecimento-com-reserva',
                'category' => 'substabelecimento',
                'description' => 'Substabelecimento mantendo os poderes do advogado original.',
                'is_system' => true,
                'content' => <<<HTML
<h2 style="text-align: center;">SUBSTABELECIMENTO</h2>
<h3 style="text-align: center;">COM RESERVA DE PODERES</h3>

<p><strong>SUBSTABELECENTE:</strong> {{advogado_nome}}, {{advogado_oab}}, com escritório em {{advogado_endereco}}.</p>

<p><strong>SUBSTABELECIDO(A):</strong> _______________________________________, inscrito(a) na OAB/_____ sob nº __________, com escritório em _________________________________________.</p>

<p><strong>CLIENTE:</strong> {{cliente_nome}}, CPF/CNPJ: {{cliente_documento}}.</p>

<p><strong>PROCESSO:</strong> {{processo_numero}}<br>
<strong>VARA:</strong> {{processo_vara}}<br>
<strong>COMARCA:</strong> {{processo_comarca}}</p>

<p>Pelo presente instrumento, o(a) advogado(a) acima qualificado(a), na qualidade de procurador(a) constituído(a) nos autos do processo em epígrafe, <strong>SUBSTABELECE, COM RESERVA DE PODERES</strong>, ao(à) advogado(a) substabelecido(a), todos os poderes que lhe foram conferidos na procuração original, para que possa atuar no referido processo com os mesmos poderes, inclusive os especiais.</p>

<p style="text-align: center;">{{data_atual}}.</p>

<br><br>

<p style="text-align: center;">_____________________________________________<br>
{{advogado_nome}}<br>
{{advogado_oab}}</p>
HTML,
            ],

            [
                'name' => 'Substabelecimento sem Reserva de Poderes',
                'slug' => 'substabelecimento-sem-reserva',
                'category' => 'substabelecimento',
                'description' => 'Substabelecimento transferindo totalmente os poderes.',
                'is_system' => true,
                'content' => <<<HTML
<h2 style="text-align: center;">SUBSTABELECIMENTO</h2>
<h3 style="text-align: center;">SEM RESERVA DE PODERES</h3>

<p><strong>SUBSTABELECENTE:</strong> {{advogado_nome}}, {{advogado_oab}}.</p>

<p><strong>SUBSTABELECIDO(A):</strong> _______________________________________, OAB/_____ nº __________.</p>

<p><strong>CLIENTE:</strong> {{cliente_nome}}</p>

<p><strong>PROCESSO:</strong> {{processo_numero}} - {{processo_vara}} - {{processo_comarca}}</p>

<p>Pelo presente, <strong>SUBSTABELEÇO SEM RESERVA DE PODERES</strong> ao(à) advogado(a) acima qualificado(a), todos os poderes que me foram conferidos pelo(a) cliente, para que atue exclusivamente no processo mencionado.</p>

<p style="text-align: center;">{{data_atual}}.</p>

<br><br>

<p style="text-align: center;">_____________________________________________<br>
{{advogado_nome}}</p>
HTML,
            ],

            // ==========================================
            // DECLARAÇÕES
            // ==========================================
            [
                'name' => 'Declaração de Comparecimento',
                'slug' => 'declaracao-comparecimento',
                'category' => 'declaracao',
                'description' => 'Declaração de comparecimento a audiência ou diligência.',
                'is_system' => true,
                'content' => <<<HTML
<h2 style="text-align: center;">DECLARAÇÃO DE COMPARECIMENTO</h2>

<p>Declaro, para os devidos fins, que o(a) Sr(a). <strong>{{cliente_nome}}</strong>, portador(a) do CPF nº {{cliente_documento}}, compareceu nesta data para:</p>

<p style="margin-left: 40px;">☐ Audiência<br>
☐ Atendimento jurídico<br>
☐ Assinatura de documentos<br>
☐ Outro: _________________________________</p>

<p><strong>Data:</strong> {{data_atual_curta}}<br>
<strong>Horário:</strong> Das _______ às _______</p>

<p><strong>Local:</strong> {{servico_local}}</p>

<p><strong>Processo relacionado:</strong> {{processo_numero}}</p>

<p>Por ser expressão da verdade, firmo a presente declaração.</p>

<p style="text-align: center;">{{data_atual}}.</p>

<br><br>

<p style="text-align: center;">_____________________________________________<br>
{{advogado_nome}}<br>
{{advogado_oab}}</p>
HTML,
            ],

            // ==========================================
            // RECIBOS
            // ==========================================
            [
                'name' => 'Recibo de Honorários',
                'slug' => 'recibo-honorarios',
                'category' => 'recibo',
                'description' => 'Recibo para pagamento de honorários advocatícios.',
                'is_system' => true,
                'content' => <<<HTML
<h2 style="text-align: center;">RECIBO DE HONORÁRIOS ADVOCATÍCIOS</h2>

<p><strong>Nº:</strong> ____________</p>

<p>Recebi de <strong>{{cliente_nome}}</strong>, CPF/CNPJ {{cliente_documento}}, a importância de <strong>{{servico_valor}}</strong> (________________________________), referente a honorários advocatícios pelos serviços prestados:</p>

<p style="margin-left: 40px;">
<strong>Serviço:</strong> {{servico_tipo}}<br>
<strong>Código:</strong> {{servico_codigo}}<br>
<strong>Processo:</strong> {{processo_numero}}<br>
<strong>Vara/Tribunal:</strong> {{processo_vara}}<br>
<strong>Comarca:</strong> {{processo_comarca}}
</p>

<p><strong>Forma de pagamento:</strong> ________________________________</p>

<p>Para clareza e documento, firmo o presente recibo.</p>

<p style="text-align: center;">{{data_atual}}.</p>

<br><br>

<p style="text-align: center;">_____________________________________________<br>
{{advogado_nome}}<br>
{{advogado_oab}}<br>
CPF: ___________________</p>
HTML,
            ],

            // ==========================================
            // RELATÓRIOS
            // ==========================================
            [
                'name' => 'Relatório de Diligência',
                'slug' => 'relatorio-diligencia',
                'category' => 'relatorio',
                'description' => 'Relatório padrão para descrição de diligências realizadas.',
                'is_system' => true,
                'content' => <<<HTML
<h2 style="text-align: center;">RELATÓRIO DE DILIGÊNCIA</h2>

<table style="width: 100%; border-collapse: collapse;">
<tr>
<td style="border: 1px solid #ccc; padding: 8px;"><strong>Código do Serviço:</strong></td>
<td style="border: 1px solid #ccc; padding: 8px;">{{servico_codigo}}</td>
</tr>
<tr>
<td style="border: 1px solid #ccc; padding: 8px;"><strong>Tipo:</strong></td>
<td style="border: 1px solid #ccc; padding: 8px;">{{servico_tipo}}</td>
</tr>
<tr>
<td style="border: 1px solid #ccc; padding: 8px;"><strong>Data/Hora:</strong></td>
<td style="border: 1px solid #ccc; padding: 8px;">{{servico_data}}</td>
</tr>
<tr>
<td style="border: 1px solid #ccc; padding: 8px;"><strong>Local:</strong></td>
<td style="border: 1px solid #ccc; padding: 8px;">{{servico_local}}</td>
</tr>
<tr>
<td style="border: 1px solid #ccc; padding: 8px;"><strong>Processo:</strong></td>
<td style="border: 1px solid #ccc; padding: 8px;">{{processo_numero}}</td>
</tr>
<tr>
<td style="border: 1px solid #ccc; padding: 8px;"><strong>Vara:</strong></td>
<td style="border: 1px solid #ccc; padding: 8px;">{{processo_vara}}</td>
</tr>
<tr>
<td style="border: 1px solid #ccc; padding: 8px;"><strong>Partes:</strong></td>
<td style="border: 1px solid #ccc; padding: 8px;">{{processo_autor}} x {{processo_reu}}</td>
</tr>
</table>

<h3>Descrição da Diligência</h3>
<p>_____________________________________________________________________________</p>
<p>_____________________________________________________________________________</p>
<p>_____________________________________________________________________________</p>

<h3>Resultado</h3>
<p>☐ Realizado com sucesso<br>
☐ Parcialmente realizado<br>
☐ Redesignado para: ___/___/_____<br>
☐ Não realizado - Motivo: ___________________________________</p>

<h3>Observações</h3>
<p>_____________________________________________________________________________</p>
<p>_____________________________________________________________________________</p>

<br><br>

<p style="text-align: center;">_____________________________________________<br>
{{advogado_nome}}<br>
{{advogado_oab}}</p>

<p style="text-align: right; font-size: 12px;">Relatório gerado em: {{data_atual}}</p>
HTML,
            ],

            // ==========================================
            // CORRESPONDÊNCIAS
            // ==========================================
            [
                'name' => 'Ofício Padrão',
                'slug' => 'oficio-padrao',
                'category' => 'correspondencia',
                'description' => 'Modelo de ofício para comunicações oficiais.',
                'is_system' => true,
                'content' => <<<HTML
<p style="text-align: right;">{{data_atual}}</p>

<p><strong>Of. nº ______/{{ano_atual}}</strong></p>

<p><strong>A(o)<br>
Ilmo(a). Sr(a). ________________________________<br>
________________________________<br>
________________________________</strong></p>

<p><strong>Ref.: {{processo_numero}}</strong></p>

<p>Prezado(a) Senhor(a),</p>

<p>_____________________________________________________________________________</p>
<p>_____________________________________________________________________________</p>
<p>_____________________________________________________________________________</p>
<p>_____________________________________________________________________________</p>

<p>Atenciosamente,</p>

<br><br>

<p>_____________________________________________<br>
<strong>{{advogado_nome}}</strong><br>
{{advogado_oab}}<br>
{{advogado_email}}<br>
{{advogado_telefone}}</p>
HTML,
            ],
        ];

        foreach ($templates as $template) {
            DocumentTemplate::firstOrCreate(
                ['slug' => $template['slug']],
                $template
            );
        }

        $this->command->info('✅ ' . count($templates) . ' templates de documentos criados!');
    }
}
