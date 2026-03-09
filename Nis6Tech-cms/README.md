# Nis6 Tech CMS | Enterprise Management Portal

This is a private, standalone administrative dashboard for the Nutpa project. 

### Why this exists:
1.  **Isolation**: This folder is completely decoupled from the main website.
2.  **Security**: It should be hosted on a private domain or a password-protected subdirectory.
3.  **Vanilla Power**: Built with zero dependencies, 100% compatible with GitHub Pages and static hosting.

### Setup:
1.  **Hosting**: You can host this folder separately from the main `index.html`.
2.  **Data**: It shares the `js/data.js` structure. Changes here can be manually synced or used to manage content.
3.  **Future-Proof**: This dashboard can be easily connected to a backend (like Firebase or a JSON API) by updating the submission logic in `index.html`.

**Note**: Do not share the URL of this dashboard with the client.
