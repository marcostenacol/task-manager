<?php

function updateLastAccess(int $user_id): void {
    DB::statement("call admin.update_last_access(?);", [$user_id]);
}
