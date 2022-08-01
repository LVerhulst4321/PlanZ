<?php
// Copyright (c) 2021, 2022 BC Holmes. All rights reserved. See copyright document for more details.
// These functions provide support for common database queries.

class DatabaseException extends Exception {};
class DatabaseSqlException extends DatabaseException {};
class DatabaseDuplicateKeyException extends DatabaseException {};

?>