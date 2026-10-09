/* Tiny promise wrapper around IndexedDB. Everything stays in this browser. */
(function () {
  'use strict';

  const NAME = 'id-card-generator';
  const VERSION = 1;
  let dbp = null;

  function open() {
    if (dbp) return dbp;
    dbp = new Promise((resolve, reject) => {
      const req = indexedDB.open(NAME, VERSION);
      req.onupgradeneeded = () => {
        const db = req.result;
        if (!db.objectStoreNames.contains('profiles')) db.createObjectStore('profiles', { keyPath: 'id' });
        if (!db.objectStoreNames.contains('members')) {
          const s = db.createObjectStore('members', { keyPath: 'id' });
          s.createIndex('profileId', 'profileId');
        }
        if (!db.objectStoreNames.contains('meta')) db.createObjectStore('meta', { keyPath: 'key' });
      };
      req.onsuccess = () => resolve(req.result);
      req.onerror = () => reject(req.error);
    });
    return dbp;
  }

  function wrap(req) {
    return new Promise((resolve, reject) => {
      req.onsuccess = () => resolve(req.result);
      req.onerror = () => reject(req.error);
    });
  }

  async function store(name, mode) {
    const db = await open();
    return db.transaction(name, mode).objectStore(name);
  }

  async function all(name) {
    return wrap((await store(name, 'readonly')).getAll());
  }

  async function byProfile(profileId) {
    return wrap((await store('members', 'readonly')).index('profileId').getAll(profileId));
  }

  async function put(name, value) {
    return wrap((await store(name, 'readwrite')).put(value));
  }

  async function putMany(name, values) {
    const db = await open();
    return new Promise((resolve, reject) => {
      const tx = db.transaction(name, 'readwrite');
      const s = tx.objectStore(name);
      values.forEach((v) => s.put(v));
      tx.oncomplete = () => resolve();
      tx.onerror = () => reject(tx.error);
    });
  }

  async function del(name, key) {
    return wrap((await store(name, 'readwrite')).delete(key));
  }

  async function deleteProfile(profileId) {
    const members = await byProfile(profileId);
    const db = await open();
    return new Promise((resolve, reject) => {
      const tx = db.transaction(['profiles', 'members'], 'readwrite');
      tx.objectStore('profiles').delete(profileId);
      const ms = tx.objectStore('members');
      members.forEach((m) => ms.delete(m.id));
      tx.oncomplete = () => resolve();
      tx.onerror = () => reject(tx.error);
    });
  }

  async function getMeta(key) {
    const row = await wrap((await store('meta', 'readonly')).get(key));
    return row ? row.value : undefined;
  }

  function setMeta(key, value) {
    return put('meta', { key, value });
  }

  window.DB = { open, all, byProfile, put, putMany, del, deleteProfile, getMeta, setMeta };
})();
