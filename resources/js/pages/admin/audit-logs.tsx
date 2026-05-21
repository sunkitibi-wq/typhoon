import { Head, Link } from '@inertiajs/react';
import { ScrollText, Globe, Monitor, Smartphone } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { router } from '@inertiajs/react';

interface Log {
    id: number; route_name: string | null; method: string; url: string;
    ip_address: string | null; status_code: number; created_at: string;
    user?: { id: number; name: string; email: string } | null;
}

export default function AuditLogs({ logs }: { logs: { data: Log[] } }) {
    const [search, setSearch] = useState('');

    return (
        <>
            <Head title="Audit Logs" />
            <div className="flex flex-col gap-6 p-4">
                <h1 className="text-2xl font-bold">Audit Logs</h1>

                <div className="flex gap-2">
                    <Input
                        placeholder="Search by route..."
                        value={search}
                        onChange={e => { setSearch(e.target.value); router.get(route('admin.audit-logs'), { route: e.target.value }, { preserveState: true }); }}
                        className="max-w-sm"
                    />
                </div>

                <Card>
                    <CardContent className="space-y-1 pt-6">
                        {logs.data.map(log => (
                            <div key={log.id} className="flex items-center justify-between rounded-lg border p-2 text-sm">
                                <div className="flex items-center gap-3 min-w-0">
                                    <Badge variant="outline" className="font-mono text-[10px] w-10 justify-center shrink-0">
                                        {log.method === 'GET' ? 'GET' : log.method === 'POST' ? 'POST' : log.method === 'DELETE' ? 'DEL' : log.method === 'PUT' ? 'PUT' : log.method === 'PATCH' ? 'PAT' : log.method}
                                    </Badge>
                                    <div className="min-w-0">
                                        <p className="font-mono text-xs truncate max-w-md">{log.url}</p>
                                        <div className="flex gap-2 text-xs text-muted-foreground">
                                            <span className="flex items-center gap-1"><Globe className="h-3 w-3" />{log.ip_address ?? 'N/A'}</span>
                                            {log.user && <span>{log.user.name}</span>}
                                            <span>{new Date(log.created_at).toLocaleString()}</span>
                                        </div>
                                    </div>
                                </div>
                                <div className="flex items-center gap-2 shrink-0">
                                    {log.route_name && <Badge variant="outline" className="text-[10px] font-mono max-w-[150px] truncate">{log.route_name}</Badge>}
                                    <Badge variant={log.status_code < 400 ? 'secondary' : 'destructive'} className="text-[10px]">{log.status_code}</Badge>
                                </div>
                            </div>
                        ))}
                        {logs.data.length === 0 && <p className="text-sm text-muted-foreground py-4 text-center">No audit logs found.</p>}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
