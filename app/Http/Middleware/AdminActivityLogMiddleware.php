<?php

namespace App\Http\Middleware;

use App\Models\AdminActivityLog;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminActivityLogMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (
            ! $request->user()
            || $request->user()->role !== 'admin'
            || ! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)
        ) {
            return $response;
        }

        $subjectType = null;
        $subjectId = null;
        foreach ($request->route()->parameters() as $parameter) {
            if ($parameter instanceof Model) {
                $subjectType = class_basename($parameter);
                $subjectId = (string) $parameter->getKey();
                break;
            }
        }

        AdminActivityLog::create([
            'actor_id' => $request->user()->getKey(),
            'action' => $request->route()?->getName() ?? 'admin.unknown',
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'http_method' => $request->method(),
            'response_status' => $response->getStatusCode(),
        ]);

        return $response;
    }
}
