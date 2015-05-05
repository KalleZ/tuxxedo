# Why? #

The new folder structure is based upon the idea for bundles.
The structure aims to bring maximum flexibility, for the creator of a bundle to decide what to do.

The current structure has some limitations, in the terms of separating the core layer from layers above. And it's also head to bring a really god implementation for installing/removing bundles, by script.


# Others? #

A change like this will probably, require some modifications to the,
design of the namespace. But this only seems logical, from this point.
Since the architecture should prepare for a big change in the engine core.


# Details #


```
index.php
        /library/
                /Config.php
                /Bundles/
                        /{BundleName}/
                                Config.php
                /Engine/
                        /Design/
                                /Bundle.php
                        Loader.php
                        Registry.php
                        Functions.php
                        Boostrap.php
                        Exception.php

```

The structure above, is the new proposed structure.
The BUndles folder, should only contain bundle packs, and thier own design structure. While engine, should only contain the bare minimum core features.