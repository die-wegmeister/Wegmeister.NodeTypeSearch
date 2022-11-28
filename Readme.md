# NodeType Search

Extend the Flow CLI to help find nodes by a given FlowQuery or node type.


## Installation

Simply run `composer require --dev wegmeister/nodetype-search` or add it to your `composer.json` and run `composer update`.


## What this package does

This package will help you to find node types that match a given node type or FlowQuery filter. 

Possible use cases are:
- Find all pages that have a form on it.
- Find all pages that have a placeholder node type (espacially useful right before launching a new site).
- Find all pages containing placeholder text


## How to use this package

After installation, simply run one of the following commands:

**Find pages containing node type Example.Package:XYZ**:
```sh
./flow nodetypesearch:findurisbynodetype "Example.Package:XYZ"
```

**Find pages using a FlowQuery filter**
```sh
./flow nodetypesearch:findurisbyflowqueryfilter "[text*=Lorem]"
```

Options (for both commands):
- `--site-node-path="/sites/site"`: Restrict search to nodes below the given "site node path" in multisite setups (side note: Can also be on a deeper level to further limit results).
- `--domain="https://domain.tld"`: Prefix the returned list of uri paths by the given domain.
- `--includeHidden`: Include hidden elements in the search. The printed list will always print a red dot for hidden pages or a green dot for visible pages.
