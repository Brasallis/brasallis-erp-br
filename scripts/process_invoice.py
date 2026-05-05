# -*- coding: utf-8 -*-
"""
# Agente de Processamento de Notas Fiscais (OCR com IA)
Este script extrai dados de uma imagem ou PDF de nota fiscal.
"""

import sys
import os
import json
import mysql.connector
import google.generativeai as genai
from PIL import Image
import argparse
import pathlib
from pdf2image import convert_from_path

def get_structured_prompt():
    """ Retorna o prompt estruturado e profissional para o modelo de IA, capaz de lidar com múltiplos tipos de notas fiscais. """
    return ("""
    **PERSONA:** Você é um agente de IA especialista em extração de dados, treinado para processar com alta precisão qualquer tipo de nota fiscal brasileira, seja de produtos (NF-e) ou de serviços (NFS-e).

    **CONTEXTO:** O usuário enviou a imagem de uma nota fiscal. Sua tarefa é analisar a imagem e extrair um conjunto específico de campos. A nota pode ser de um tipo (serviço) e não conter campos de outro (produtos), e vice-versa. Sua extração deve ser robusta a essa variação.

    **TAREFA:** Analise a imagem e retorne **um único objeto JSON** contendo os campos listados abaixo.

    **INSTRUÇÕES DETALHADAS PARA EXTRAÇÃO:**

    2.  **Campos de Produto (NF-e):**
        - `numero_nota`: (string) O número da NF-e.
        - `valor_total`: (string) O valor total final da nota. Use ponto como separador decimal. Ex: "199.99".
        - `nome_fornecedor`: (string) A razão social ou nome fantasia do fornecedor/emitente.
        - `cnpj_fornecedor`: (string) O CNPJ do fornecedor/emitente. Retorne apenas os números.
        - `nome_destinatario`: (string) A razão social ou nome fantasia do destinatário (cliente em saídas).
        - `cnpj_destinatario`: (string) O CNPJ do destinatário (cliente em saídas). Retorne apenas os números.
        - `itens`: (array de objetos) Uma lista de todos os produtos. Cada objeto deve ter: 
            - `descricao` (string)
            - `quantidade` (number)
            - `valor_unitario` (number)
            - `valor_total_item` (number)
            - `ncm` (string, apenas números)
            - `cst_csosn` (string, código de situação tributária)
            - `cfop` (string, código fiscal de operações)

    3.  **Campos de Serviço (NFS-e):**
        - `chave_acesso_nfde`: (string) A Chave de Acesso ou Código de Verificação da NFS-e. É um código longo.
        - `cnpj_tomador_servico`: (string) O CNPJ do "Tomador de Serviço". Retorne apenas os números.
        - `codigo_tributacao_nacional`: (string) O "Código de Tributação Nacional" ou "Código do Serviço".

    4.  **Campo Comum:**
        - `data_emissao`: (string) A data de emissão da nota, no formato "AAAA-MM-DD".

    **REGRAS CRÍTICAS DE SAÍDA:**
    - **SAÍDA ÚNICA:** Sua resposta deve ser **APENAS** o objeto JSON. Não inclua texto, explicações ou formatação como ```json.
    - **CAMPOS AUSENTES:** Se um campo solicitado **não existir** na imagem da nota (ex: `chave_acesso_nfde` em uma nota de produtos), o valor da chave no JSON deve ser `null`. **Não invente dados.**
    - **PRECISÃO:** Seja extremamente preciso. Verifique novamente os números e a formatação da data.

    **EXEMPLO DE SAÍDA PARA UMA NOTA DE PRODUTO:**
    ```json
    {
      "numero_nota": "12345",
      "valor_total": "150.00",
      "nome_fornecedor": "FORNECEDOR EXEMPLO LTDA",
      "cnpj_fornecedor": "12345678000199",
      "itens": [
        {
            "descricao": "PRODUTO A",
            "quantidade": 10,
            "valor_unitario": 15.00,
            "valor_total_item": 150.00,
            "ncm": "22021000",
            "cst_csosn": "060",
            "cfop": "5405"
        }
      ],
      "chave_acesso_nfde": null,
      "cnpj_tomador_servico": null,
      "codigo_tributacao_nacional": null,
      "data_emissao": "2025-10-06"
    }
    ```
    """)

def process_with_gemini(image_object, api_key):
    """ Processa um objeto de imagem Pillow usando a API do Google Gemini e retorna tanto os dados processados quanto a resposta bruta. """
    # print("Processando com Gemini...")
    try:
        genai.configure(api_key=api_key)
        model = genai.GenerativeModel('models/gemini-flash-latest')
        prompt = get_structured_prompt()
        response = model.generate_content([prompt, image_object])
        
        raw_response_text = response.text.strip()
        # Lógica aprimorada para encontrar o JSON
        json_start = raw_response_text.find('{')
        json_end = raw_response_text.rfind('}') + 1
        
        if json_start != -1 and json_end != 0:
            cleaned_response = raw_response_text[json_start:json_end]
            return json.loads(cleaned_response), raw_response_text
        else:
            raise ValueError("Nenhum JSON válido encontrado na resposta da IA.")

    except Exception as e:
        print(f"Erro ao processar com Gemini: {e}")
        raise


def update_db(db_config, compra_id, status, extracted_data=None, raw_response=None, error_message=None):
    """Atualiza o status e os dados extraídos no banco de dados, lidando com todos os campos possíveis."""
    cnx = None
    try:
        cnx = mysql.connector.connect(**db_config)
        cursor = cnx.cursor()

        if status == 'processado' and extracted_data:
            sql = ("UPDATE dados_nota_fiscal SET "
                   "status = %s, numero_nota = %s, data_emissao = %s, valor_total = %s, "
                   "nome_fornecedor = %s, cnpj_fornecedor = %s, itens_json = %s, "
                   "chave_acesso_nfde = %s, cnpj_tomador_servico = %s, codigo_tributacao_nacional = %s, "
                   "raw_ai_response = %s, texto_completo = %s "
                   "WHERE compra_id = %s")
            params = (
                status,
                extracted_data.get('numero_nota'),
                extracted_data.get('data_emissao'),
                extracted_data.get('valor_total'),
                extracted_data.get('nome_fornecedor'),
                extracted_data.get('cnpj_fornecedor'),
                json.dumps(extracted_data.get('itens')),
                extracted_data.get('chave_acesso_nfde'),
                extracted_data.get('cnpj_tomador_servico'),
                extracted_data.get('codigo_tributacao_nacional'),
                raw_response,
                json.dumps(extracted_data),
                compra_id
            )
        else: # erro
            sql = "UPDATE dados_nota_fiscal SET status = %s, texto_completo = %s, raw_ai_response = %s WHERE compra_id = %s"
            params = (status, str(error_message), raw_response, compra_id)

        cursor.execute(sql, params)
        cnx.commit()
        print(f"Status da compra {compra_id} atualizado para '{status}'.")
    except Exception as e:
        print(f"Erro CRÍTICO de banco de dados ao tentar atualizar status: {e}")
    finally:
        if cnx and cnx.is_connected():
            cursor.close()
            cnx.close()

def main(args):
    if not args.preview:
        print(f"Processamento real iniciado para a compra ID: {args.compra_id}")
    
    # Obter credenciais e chave de API das variáveis de ambiente
    db_host = os.environ.get('DB_HOST')
    db_user = os.environ.get('DB_USER')
    db_pass = os.environ.get('DB_PASS')
    db_name = os.environ.get('DB_NAME')
    api_key = os.environ.get('GEMINI_API_KEY')

    if not all([db_host, db_user, db_pass, db_name, api_key]):
        # Se for preview, podemos tolerar falta de DB, mas precisamos da API KEY
        if args.preview and api_key:
            pass # Continua
        else:
            error_msg = "Erro de configuração: As variáveis de ambiente do banco de dados e da API não estão definidas."
            print(error_msg)
            if not args.preview:
                # Tenta conectar ao DB com o que tiver para logar o erro lá
                db_config_for_error = {
                    'host': db_host, 'user': db_user, 
                    'password': db_pass, 'database': db_name
                }
                update_db(db_config_for_error, args.compra_id, 'erro', error_message=error_msg)
            sys.exit(1)

    db_config = {
        'host': db_host,
        'user': db_user,
        'password': db_pass,
        'database': db_name
    }
    raw_response_for_db = None
    try:
        file_path = pathlib.Path(args.file_path)
        image_object = None

        if file_path.suffix.lower() == '.pdf':
            # print("Arquivo PDF detectado. Convertendo para imagem...") 
            try:
                images = convert_from_path(file_path, first_page=1, last_page=1)
                if not images:
                    raise ValueError("Não foi possível converter o PDF em imagem.")
                image_object = images[0]
            except Exception as pdf_err:
                 # Check if it's likely a Poppler missing error
                 if "poppler" in str(pdf_err).lower() or "not in path" in str(pdf_err).lower():
                     raise ValueError("O sistema precisa do 'Poppler' instalado para ler PDF. Por favor, envie a nota em formato de IMAGEM (.jpg, .png) ou instale o Poppler.")
                 else:
                     raise ValueError(f"Erro ao processar PDF: {pdf_err}")
        else:
            # print("Arquivo de imagem detectado.")
            image_object = Image.open(file_path)

        extracted_data, raw_response_for_db = process_with_gemini(image_object, api_key)

        if args.preview:
            # Em modo preview, imprimimos APENAS o JSON para ser capturado pelo PHP
            print(json.dumps(extracted_data))
        else:
            print(f"Processamento concluído. Dados extraídos: {extracted_data}")
            update_db(db_config, args.compra_id, 'pendente_confirmacao', extracted_data, raw_response=raw_response_for_db)

    except Exception as e:
        if args.preview:
            print(json.dumps({"error": str(e)}))
        else:
            print(f"Ocorreu um erro no processamento principal: {e}")
            error_msg = f"Erro na execução: {e}."
            update_db(db_config, args.compra_id, 'erro', raw_response=raw_response_for_db, error_message=error_msg)

if __name__ == "__main__":
    parser = argparse.ArgumentParser(description='Processa uma nota fiscal usando IA.')
    parser.add_argument('compra_id', help='O ID da compra no banco de dados.')
    parser.add_argument('file_path', help='O caminho para o arquivo de imagem/PDF da nota fiscal.')
    parser.add_argument('--service', choices=['gemini', 'ollama'], required=True, help='O serviço de IA a ser utilizado.')
    parser.add_argument('--preview', action='store_true', help='Se definido, apenas retorna o JSON e não atualiza o banco de dados.')

    args = parser.parse_args()
    main(args)
