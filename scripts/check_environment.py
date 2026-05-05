# -*- coding: utf-8 -*-
"""
Script de diagnóstico para verificar se todos os componentes para a IA de OCR estão funcionando.
"""

import os
import sys

def print_status(component, status, message=""):
    status_icon = "\033[92mOK\033[0m" if status else "\033[91mFALHA\033[0m"
    print(f"- {component:<15} [ {status_icon} ] {message}")

print("--- Iniciando verificação do ambiente de IA ---")

# 1. Verificar Tesseract
# Apenas checa se o caminho que hardcodamos no script principal existe.
tesseract_ok = False
tesseract_path = r'''C:\Program Files\Tesseract-OCR\tesseract.exe'''
if os.path.exists(tesseract_path):
    tesseract_ok = True
    print_status("Tesseract", True, f"Executável encontrado em {tesseract_path}")
else:
    print_status("Tesseract", False, f"Executável NÃO encontrado em {tesseract_path}")

# 2. Verificar Poppler
# Tenta converter uma página de um PDF. Se falhar, é porque o Poppler não está no PATH.
poppler_ok = False
pdf_path_to_test = r'''C:\Users\jrlom\Downloads\NF Fernando Lombardo SET24.pdf'''
if not os.path.exists(pdf_path_to_test):
    print_status("Poppler", False, f"Arquivo de teste PDF não encontrado em {pdf_path_to_test}")
else:
    try:
        from pdf2image import convert_from_path
        from pdf2image.exceptions import PDFInfoNotInstalledError

        # Tenta converter apenas a primeira página para ser rápido
        convert_from_path(pdf_path_to_test, first_page=1, last_page=1)
        poppler_ok = True
        print_status("Poppler", True, "Conseguiu converter página de PDF.")
    except PDFInfoNotInstalledError:
        print_status("Poppler", False, "Poppler não encontrado. Verifique se a pasta 'bin' foi adicionada ao PATH do sistema.")
    except Exception as e:
        print_status("Poppler", False, f"Erro inesperado ao testar: {e}")

# 3. Verificar Ollama e Gemma
ollama_ok = False
try:
    import ollama
    client = ollama.Client()
    response = client.list()
    models = [model['name'] for model in response['models']]
    
    if any('gemma' in model for model in models):
        ollama_ok = True
        print_status("Ollama/Gemma", True, "Ollama rodando e modelo 'gemma' encontrado.")
    else:
        print_status("Ollama/Gemma", False, "Ollama está rodando, mas o modelo 'gemma' não foi encontrado. Rode 'ollama run gemma'.")
except ImportError:
    print_status("Ollama/Gemma", False, "A biblioteca 'ollama' não está instalada.")
except Exception as e:
    print_status("Ollama/Gemma", False, f"Não foi possível conectar ao Ollama. Ele está em execução? Erro: {e}")


# Resumo Final
print("--- Verificação Concluída ---")
if tesseract_ok and poppler_ok and ollama_ok:
    print("\033[92mAmbiente configurado corretamente! A funcionalidade de IA deve funcionar.\033[0m")
else:
    print("\033[91mForam encontrados problemas no ambiente. Por favor, verifique os itens com [ FALHA ] acima.\033[0m")
    sys.exit(1) # Sai com código de erro para indicar falha
