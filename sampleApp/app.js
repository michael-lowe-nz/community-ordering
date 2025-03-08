console.log("Im here");

const express = require('express');
const app = express();
const port = 80;

// Define a route that responds with some text
app.get('/', (req, res) => {
    res.send(`<h1>Hello<h1><p>This is a response from your Node.js app!</p><p>${new Date().toISOString()}</p>`);
});

// Start the server
app.listen(port, () => {
    console.log(`Server is running on http://localhost:${port}`);
});