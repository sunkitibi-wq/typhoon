import { Head, useForm, router } from '@inertiajs/react';
import { Key, Plus, Copy, CheckCircle, XCircle, Eye, EyeOff } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import InputError from '@/components/input-error';
import { useState } from 'react';

interface Client { id: number; name: string; client_id: string; client_secret: string; scopes: string[]; status: string; created_at: string; last_used_at: string | null; }

export default function ApiClients({ clients, flash }: { clients: Client[]; flash: { client_secret?: string } | null }) {
    const { data, setData, post, processing, errors, reset } = useForm({ name: '', scopes: '' });
    const [showSecret, setShowSecret] = useState(false);
    const [copied, setCopied] = useState(false);

    const submit = (e: React.FormEvent) => { e.preventDefault(); post(route('admin.api-clients'), { onSuccess: () => reset() }); };

    const copySecret = () => {
        if (flash?.client_secret) {
            navigator.clipboard.writeText(flash.client_secret);
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
        }
    };

    const revoke = (id: number) => {
        if (confirm('Revoke this API client? Existing tokens will stop working.')) {
            router.post(route('admin.api-clients.revoke', id));
        }
    };

    return (
        <>
            <Head title="API Clients" />
            <div className="flex flex-col gap-6 p-4">
                <h1 className="text-2xl font-bold">API Client Management</h1>

                {flash?.client_secret && (
                    <Card className="border-amber-500 bg-amber-50 dark:bg-amber-900/10">
                        <CardContent className="pt-6">
                            <p className="font-semibold text-amber-800 dark:text-amber-200">Client Secret (shown once)</p>
                            <div className="flex gap-2 mt-2">
                                <code className="flex-1 rounded bg-amber-100 dark:bg-amber-900/30 px-3 py-2 text-sm font-mono break-all">
                                    {showSecret ? flash.client_secret : '••••••••••••••••••••••••••••••••••••••••••••••••••'}
                                </code>
                                <Button variant="outline" size="icon" onClick={() => setShowSecret(!showSecret)}>
                                    {showSecret ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                                </Button>
                                <Button variant="outline" size="icon" onClick={copySecret}>
                                    {copied ? <CheckCircle className="h-4 w-4 text-green-600" /> : <Copy className="h-4 w-4" />}
                                </Button>
                            </div>
                            <p className="mt-2 text-xs text-amber-600 dark:text-amber-400">Store this securely — it cannot be retrieved later.</p>
                        </CardContent>
                    </Card>
                )}

                <Card>
                    <CardHeader><CardTitle><Plus className="mr-2 inline h-4 w-4" />Create API Client</CardTitle></CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-4">
                            <div className="grid gap-4 md:grid-cols-2">
                                <div className="space-y-2">
                                    <Label>Client Name *</Label>
                                    <Input value={data.name} onChange={e => setData('name', e.target.value)} placeholder="My App" required />
                                    <InputError message={errors.name} />
                                </div>
                                <div className="space-y-2">
                                    <Label>Scopes (comma-separated)</Label>
                                    <Input value={data.scopes} onChange={e => setData('scopes', e.target.value)} placeholder="read,write,admin" />
                                    <p className="text-xs text-muted-foreground">Leave empty for read-only access</p>
                                </div>
                            </div>
                            <Button type="submit" disabled={processing}><Key className="mr-1 h-4 w-4" />Create Client</Button>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader><CardTitle>Your API Clients ({clients.length})</CardTitle></CardHeader>
                    <CardContent className="space-y-3">
                        {clients.length === 0 && <p className="text-sm text-muted-foreground">No API clients yet.</p>}
                        {clients.map(c => (
                            <div key={c.id} className="flex items-center justify-between rounded-lg border p-3">
                                <div className="flex items-center gap-3">
                                    <Key className="size-5 text-muted-foreground" />
                                    <div>
                                        <p className="font-medium">{c.name}</p>
                                        <p className="font-mono text-xs text-muted-foreground">{c.client_id}</p>
                                        <div className="flex gap-2 mt-1">
                                            {c.scopes.map(s => <Badge key={s} variant="secondary" className="text-[10px]">{s}</Badge>)}
                                        </div>
                                        <p className="text-xs text-muted-foreground mt-1">
                                            Created {new Date(c.created_at).toLocaleDateString()}
                                            {c.last_used_at && ` · Last used ${new Date(c.last_used_at).toLocaleDateString()}`}
                                        </p>
                                    </div>
                                </div>
                                <div className="flex items-center gap-2">
                                    <Badge variant={c.status === 'active' ? 'default' : 'destructive'}>{c.status}</Badge>
                                    {c.status === 'active' && <Button variant="outline" size="sm" onClick={() => revoke(c.id)}>Revoke</Button>}
                                </div>
                            </div>
                        ))}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
