/**
 * Firebase Configuration for OS Chennai CMS
 * Replace the dummy values below with your actual Firebase Project credentials.
 */

// Placeholder for user's Firebase config
const firebaseConfig = {
    apiKey: "AIzaSyCD-nF2tPRMV-CZWpmSj38zziw5NFuMXko",
    authDomain: "OS CHENNAI-cms.firebaseapp.com",
    projectId: "OS CHENNAI-cms",
    storageBucket: "OS CHENNAI-cms.firebasestorage.app",
    messagingSenderId: "121790570276",
    appId: "1:121790570276:web:692a06b2ad1f54450c9db1",
    measurementId: "G-4FDGCWV9LQ"
};

// Initialize Firebase (Compat version)
let db;
let analytics;
let storage;

function initFirebase() {
    if (typeof firebase !== 'undefined') {
        firebase.initializeApp(firebaseConfig);
        db = firebase.firestore();
        storage = firebase.storage();
        if (firebase.analytics) analytics = firebase.analytics();
        console.log("Firebase Cloud & Storage Initialized.");
        return true;
    }
    console.warn("Firebase scripts not loaded.");
    return false;
}
