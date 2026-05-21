import { Head, router } from '@inertiajs/react';
import { Bell, CheckCheck, Info, AlertTriangle, CheckCircle, XCircle, ArrowRight } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';

interface Notification { id: number; type: string; title: string; body: string; data: Record<string, unknown> | null; status: string; read_at: string | null; created_at: string; }
interface PaginatedData { data: Notification[]; }

export default function Notifications({ notifications, unread_count }: { notifications: PaginatedData; unread_count: number }) {
    const markRead = (id?: number) => {
        router.post(route('banking.notifications'), id ? { notification_id: id } : {});
    };

    const typeIcon = (type: string) => {
        switch (type) {
            case 'alert': return <AlertTriangle className="size-5 text-amber-500" />;
            case 'success': return <CheckCircle className="size-5 text-emerald-500" />;
            case 'error': return <XCircle className="size-5 text-rose-500" />;
            default: return <Info className="size-5 text-blue-500" />;
        }
    };

    return (
        <>
            <Head title="Notifications" />
            <div className="flex flex-col gap-6 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-bold">Notifications</h1>
                    {unread_count > 0 && (
                        <Button variant="outline" size="sm" onClick={() => markRead()}>
                            <CheckCheck className="mr-1 h-4 w-4" />Mark All Read ({unread_count})
                        </Button>
                    )}
                </div>

                <Card>
                    <CardContent className="space-y-3 pt-6">
                        {notifications.data.length === 0 && <p className="py-8 text-center text-sm text-muted-foreground">No notifications yet.</p>}
                        {notifications.data.map(n => (
                            <div key={n.id} className={`flex items-start justify-between rounded-lg border p-3 ${!n.read_at ? 'border-l-4 border-l-primary bg-accent/30' : ''}`}>
                                <div className="flex gap-3">
                                    {typeIcon(n.type)}
                                    <div>
                                        <p className={`font-medium ${!n.read_at ? 'font-semibold' : ''}`}>{n.title}</p>
                                        <p className="text-sm text-muted-foreground">{n.body}</p>
                                        <div className="flex gap-3 mt-1 text-xs text-muted-foreground">
                                            <span>{new Date(n.created_at).toLocaleString()}</span>
                                            <Badge variant="outline" className="text-[10px]">{n.type}</Badge>
                                        </div>
                                    </div>
                                </div>
                                {!n.read_at && (
                                    <Button variant="ghost" size="sm" onClick={() => markRead(n.id)}>
                                        <CheckCheck className="h-4 w-4" />
                                    </Button>
                                )}
                            </div>
                        ))}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
