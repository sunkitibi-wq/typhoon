import { Head, router } from '@inertiajs/react';
import { AlertTriangle, CheckCircle, Clock } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

interface Alert {
    id: number; alert_type: string; severity: string; status: string;
    description: string; amount: number; created_at: string;
}

export default function Monitoring({ alerts, open_count }: { alerts: Alert[]; open_count: number }) {
    const resolve = (id: number) => router.post(route('admin.monitoring.resolve', id));

    const severityColor = (s: string) => {
        switch (s) { case 'high': return 'destructive'; case 'medium': return 'secondary'; default: return 'outline'; }
    };

    return (
        <>
            <Head title="Compliance Monitoring" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <h2 className="text-2xl font-bold tracking-tight">Compliance Monitoring</h2>
                <div className="grid gap-4 md:grid-cols-3">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium">Open Alerts</CardTitle>
                            <AlertTriangle className="size-4 text-rose-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-rose-500">{open_count}</div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium">Resolved</CardTitle>
                            <CheckCircle className="size-4 text-emerald-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-emerald-500">{alerts.filter(a => a.status === 'resolved').length}</div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium">Total</CardTitle>
                            <Clock className="size-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{alerts.length}</div>
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader><CardTitle>Alerts</CardTitle></CardHeader>
                    <CardContent className="space-y-3">
                        {alerts.length === 0 && <p className="text-sm text-muted-foreground">No alerts.</p>}
                        {alerts.map(a => (
                            <div key={a.id} className={`flex items-center justify-between rounded-lg border p-3 ${a.status === 'open' ? 'border-l-4 border-l-rose-500' : ''}`}>
                                <div className="flex items-center gap-3">
                                    <AlertTriangle className={`size-5 ${a.severity === 'high' ? 'text-rose-500' : 'text-amber-500'}`} />
                                    <div>
                                        <p className="font-medium">{a.alert_type}</p>
                                        <p className="text-xs text-muted-foreground">{a.description}</p>
                                        <div className="flex gap-2 mt-1 text-xs text-muted-foreground">
                                            <span>€{Number(a.amount).toFixed(2)}</span>
                                            <span>{new Date(a.created_at).toLocaleDateString()}</span>
                                        </div>
                                    </div>
                                </div>
                                <div className="flex items-center gap-2">
                                    <Badge variant={severityColor(a.severity)}>{a.severity}</Badge>
                                    <Badge variant={a.status === 'open' ? 'destructive' : 'secondary'}>{a.status}</Badge>
                                    {a.status === 'open' && (
                                        <Button variant="outline" size="sm" onClick={() => resolve(a.id)}>
                                            <CheckCircle className="mr-1 h-3 w-3" />Resolve
                                        </Button>
                                    )}
                                </div>
                            </div>
                        ))}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
