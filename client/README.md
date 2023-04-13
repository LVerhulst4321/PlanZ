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

Your browser should automatically open http://localhost:8080. At the moment, '/' is mapped to a login page.
This log in page will allow you to log in locally, but it's a different login page than is used on the main app,
and isn't used in production. Nonetheless, if you log in, you'll set up the correct PHP session id cookie,
which means you can invoke any API endpoint.

The React app can be mostly developed locally, but the following limitations apply:

- the header and footer are static placeholders; normally, the header and footer are rendered by the server.
- you can log in, but there's to automatic navigation. You need to know the url of the page you're trying
  to go to, and manually add that in the navigation bar.
- we don't currently import the zambia*.css files into the dev version of the app; some styles that only
  exist in those files will not appear in your browser.