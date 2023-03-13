# React Client for PlanZ

This code provides a React client for PlanZ. At the moment, it is only used for a handful of functions.

## Building/Installing the React client

To create the client, do the following:

First: make sure that you have [Node.js and npm installed](https://nodejs.org/en/download/).

1. `cd client`
2. Run `npm install`
3. Run `npm run package`
4. upload the dist/planzReactApp.js file to a dist directory on the server

## Running the React client locally

You can run the React client locally. At the moment, this has some significant limitations. To run the
React client locally:

1. `cd client`
2. Change the proxy entry in the package.json to point to your server
3. Run `npm install`
4. Run `npm run start`

Your browser should automatically open http://localhost:8080. At the moment, '/' is mapped to an
unknown page. But if, for example, you manually open http://localhost:8080/brainstorm.php, you should see
the brainstorm page. Note that these things are true:

- the header and footer are static placeholders; normally, the header and footer are rendered by the server.
- server requests currently fail because there's no way to log in.