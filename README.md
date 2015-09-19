WikibaseImport
===============

WikibaseImport is a MediaWiki extension that provides a maintenance script for importing entities from another Wikibase instance. (e.. Wikidata)

The script imports the specified entity or entities, including statements, sitelinks, labels, descriptions and aliases. The extension tracks the newly assigned entity id and the original id, so that any other entity that references can be linked appropriately.

The script also imports any referenced entities (e.g. properties, badge items, wikibase-item values) without the statements.

Usage
------

Import a specific entity:

```
php maintenance/importEntities.php --entity Q147
```

Import a batch of entities:

```
php maintenance/importEntities.php --file elements.csv
```

You need to create the csv with a list of entity ids. For example, get a list
of entity ids from a query (e.g. Wikidata sparql).

Import all properties:

```
php maintenance/importEntities.php --all-properties
```
