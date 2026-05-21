import { Head, useForm } from '@inertiajs/react';
import { Users, UserPlus } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import InputError from '@/components/input-error';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

interface TeamMember {
    id: number;
    name: string;
    email: string;
    role: string;
    spending_limit: number;
    status: string;
    accepted_at: string | null;
}

export default function Team({ team }: { team: TeamMember[] }) {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
        role: 'finance',
        spending_limit: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('corporate.team'));
    };

    return (
        <>
            <Head title="Team Management" />
            <div className="flex flex-col gap-6">
                <div>
                    <h1 className="text-2xl font-bold">Team Management</h1>
                    <p className="text-muted-foreground">Manage your team members and their permissions</p>
                </div>

                <Card>
                    <CardHeader>
                        <div className="flex items-center gap-2">
                            <UserPlus className="h-5 w-5" />
                            <CardTitle>Invite Team Member</CardTitle>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-4">
                            <div className="grid gap-4 md:grid-cols-3">
                                <div className="space-y-2">
                                    <Label htmlFor="email">Email</Label>
                                    <Input id="email" type="email" value={data.email} onChange={e => setData('email', e.target.value)} required placeholder="colleague@company.com" />
                                    <InputError message={errors.email} />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="role">Role</Label>
                                    <Select value={data.role} onValueChange={v => setData('role', v)}>
                                        <SelectTrigger><SelectValue /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="admin">Admin</SelectItem>
                                            <SelectItem value="finance">Finance</SelectItem>
                                            <SelectItem value="operator">Operator</SelectItem>
                                            <SelectItem value="viewer">Viewer</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="spending_limit">Spending Limit</Label>
                                    <Input id="spending_limit" type="number" step="0.01" value={data.spending_limit} onChange={e => setData('spending_limit', e.target.value)} placeholder="0.00" />
                                </div>
                            </div>
                            <Button type="submit" disabled={processing}>
                                <UserPlus className="mr-2 h-4 w-4" /> Invite
                            </Button>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <div className="flex items-center gap-2">
                            <Users className="h-5 w-5" />
                            <CardTitle>Team Members ({team.length})</CardTitle>
                        </div>
                    </CardHeader>
                    <CardContent>
                        {team.length === 0 ? (
                            <p className="text-sm text-muted-foreground">No team members yet. Invite your first member above.</p>
                        ) : (
                            <div className="space-y-3">
                                {team.map((member) => (
                                    <div key={member.id} className="flex items-center justify-between rounded-lg border p-3">
                                        <div>
                                            <p className="font-medium">{member.name}</p>
                                            <p className="text-sm text-muted-foreground">{member.email}</p>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <Badge variant="outline">{member.role}</Badge>
                                            <Badge variant={member.status === 'active' ? 'default' : 'secondary'}>
                                                {member.status}
                                            </Badge>
                                            {member.spending_limit > 0 && (
                                                <span className="text-xs text-muted-foreground">
                                                    €{member.spending_limit.toLocaleString()} limit
                                                </span>
                                            )}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
