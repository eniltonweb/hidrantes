// Inicialização do IndexedDB
let db;
let request = indexedDB.open('InspecaoDB', 1);

request.onupgradeneeded = function(event) {
    db = event.target.result;
    
    // Criação de um object store para as inspeções
    let inspecaoStore = db.createObjectStore('inspecoes', { keyPath: 'codigo' });

    // Criação de um object store para os logs
    let logsStore = db.createObjectStore('logs', { autoIncrement: true });
    
    // Criação de um object store para configurações ou outros dados, se necessário
    let configStore = db.createObjectStore('configuracoes', { keyPath: 'chave' });
};

request.onsuccess = function(event) {
    db = event.target.result;
    console.log('IndexedDB pronto para uso');
};

request.onerror = function(event) {
    console.error('Erro ao abrir IndexedDB:', event.target.errorCode);
};

// Função para salvar inspeção localmente
function salvarInspecaoLocal(inspecao) {
    let transaction = db.transaction(['inspecoes'], 'readwrite');
    let objectStore = transaction.objectStore('inspecoes');
    objectStore.put(inspecao); // Usa put() para adicionar ou atualizar
    console.log('Inspeção salva localmente:', inspecao);
}

// Função para salvar logs locais
function salvarLogLocal(log) {
    let transaction = db.transaction(['logs'], 'readwrite');
    let objectStore = transaction.objectStore('logs');
    objectStore.add(log);
    console.log('Log salvo localmente:', log);
}

// Função para sincronizar inspeções com o servidor
function syncInspecoes() {
    return new Promise((resolve, reject) => {
        let transaction = db.transaction('inspecoes', 'readonly');
        let objectStore = transaction.objectStore('inspecoes');
        let request = objectStore.getAll();

        request.onsuccess = function(event) {
            let inspecoes = event.target.result;
            if (inspecoes.length > 0) {
                fetch('api/sync-inspecao', {
                    method: 'POST',
                    body: JSON.stringify(inspecoes),
                    headers: {
                        'Content-Type': 'application/json'
                    }
                }).then(response => {
                    if (response.ok) {
                        let deleteTransaction = db.transaction('inspecoes', 'readwrite');
                        let deleteStore = deleteTransaction.objectStore('inspecoes');
                        inspecoes.forEach(inspecao => {
                            deleteStore.delete(inspecao.codigo);
                        });
                        console.log('Inspeções sincronizadas e removidas do IndexedDB');
                        resolve();
                    } else {
                        console.error('Erro ao sincronizar inspeções:', response.statusText);
                        reject();
                    }
                }).catch(error => {
                    console.error('Erro ao sincronizar inspeções:', error);
                    reject();
                });
            } else {
                resolve(); // Não há inspeções para sincronizar
            }
        };

        request.onerror = function(event) {
            console.error('Erro ao buscar inspeções para sincronizar:', event.target.errorCode);
            reject();
        };
    });
}

// Função para sincronizar logs com o servidor
function syncLogs() {
    return new Promise((resolve, reject) => {
        let transaction = db.transaction('logs', 'readonly');
        let objectStore = transaction.objectStore('logs');
        let request = objectStore.getAll();

        request.onsuccess = function(event) {
            let logs = event.target.result;
            if (logs.length > 0) {
                fetch('api/sync-logs', {
                    method: 'POST',
                    body: JSON.stringify(logs),
                    headers: {
                        'Content-Type': 'application/json'
                    }
                }).then(response => {
                    if (response.ok) {
                        let deleteTransaction = db.transaction('logs', 'readwrite');
                        let deleteStore = deleteTransaction.objectStore('logs');
                        logs.forEach(log => {
                            deleteStore.delete(log.id);
                        });
                        console.log('Logs sincronizados e removidos do IndexedDB');
                        resolve();
                    } else {
                        console.error('Erro ao sincronizar logs:', response.statusText);
                        reject();
                    }
                }).catch(error => {
                    console.error('Erro ao sincronizar logs:', error);
                    reject();
                });
            } else {
                resolve(); // Não há logs para sincronizar
            }
        };

        request.onerror = function(event) {
            console.error('Erro ao buscar logs para sincronizar:', event.target.errorCode);
            reject();
        };
    });
}

// Função para iniciar a sincronização dos dados
function iniciarSincronizacao() {
    if ('serviceWorker' in navigator && 'SyncManager' in window) {
        navigator.serviceWorker.ready.then(function(registration) {
            return registration.sync.register('sync-inspecoes');
        }).catch(function(err) {
            console.error('Erro ao registrar sincronização', err);
        });
    } else {
        // Sincroniza manualmente se Background Sync não estiver disponível
        syncInspecoes().then(syncLogs).catch(error => {
            console.error('Erro ao sincronizar dados manualmente:', error);
        });
    }
}
