<?php
/**
 * BRASALLIS HUB — Middleware de Proteção de Scripts de Manutenção
 * 
 * Inclua este arquivo no INÍCIO de qualquer script de diagnóstico/manutenção.
 * Ele exige a MASTER_KEY via header Authorization para autorizar o acesso.
 * 
 * Uso via curl:
 *   curl -H "Authorization: Bearer SUA_MASTER_KEY" https://seusite.com/diagnose_db.php
 */

require_once dirname(__DIR__) . '/bootstrap.php';
check_master_key();

// Garante que erros aparecem somente se autorizado
ini_set('display_errors', 1);
error_reporting(E_ALL);
